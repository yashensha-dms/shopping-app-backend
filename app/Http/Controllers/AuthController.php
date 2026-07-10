<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Mail\ForgotPassword;
use App\Enums\WalletPointsDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Events\SignUpBonusPointsEvent;
use App\Http\Traits\WalletPointsTrait;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;
use App\GraphQL\Exceptions\ExceptionHandler;

class AuthController extends Controller
{
    use WalletPointsTrait;
    protected $smsService;

    public function __construct(\App\Services\Sms\SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * @OA\Post(
     *      path="/login",
     *      operationId="login",
     *      tags={"Authentication"},
     *      summary="User Login",
     *      description="Login with email and password to get access token",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="password123")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful login",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string", example="1|abc123..."),
     *              @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *              @OA\Property(property="success", type="boolean", example=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid credentials"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error"
     *      )
     * )
     */
    public function login(Request $request)
    {
        $user = $this->verifyLogin($request);
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'The entered credentials are incorrect, Please try Again!', 'success' => false], 400);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->tokens()->update([
            'role_type' => $user->getRoleNames()->first()
        ]);

        return [
            'access_token' => $token,
            'permissions'  => $user->getAllPermissions(),
            'success' => true
        ];
    }

    public function verifyVendor(User $vendor)
    {
        if (Helpers::isMultiVendorEnable()) {
            if ($vendor?->store?->first()?->is_approved) {
                return true;
            }

            throw new Exception('Please await store approval before logging in.', 403);
        }

        throw new Exception('The multi-vendor feature is currently deactivated.', 403);
    }

    /**
     * @OA\Post(
     *      path="/backend/login",
     *      operationId="backendLogin",
     *      tags={"Admin Authentication"},
     *      summary="Admin / Staff Login",
     *      description="Authenticate as an Admin or Vendor to get an administrative token.",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="admin@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="password123")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Login successful",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string", example="1|abc123..."),
     *              @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *              @OA\Property(property="success", type="boolean", example=true)
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid credentials"
     *      )
     * )
     */
    public function backendLogin(Request $request)
    {
        $user = $this->verifyLogin($request);
        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'The entered backend credentials are incorrect, Please try Again!', 'success' => false], 400);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        if ($user->hasRole(RoleEnum::VENDOR)) {
            $this->verifyVendor($user);
        }

        $user->tokens()->update([
            'role_type' => $user->getRoleNames()->first()
        ]);

        return [
            'access_token' => $token,
            'permissions'  => $user->getAllPermissions(),
            'success' => true
        ];
    }

    public function verifyLogin(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email'    => 'required_without:phone',
            'phone'    => 'required_without:email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $user = User::where(function($query) use ($request) {
            if ($request->email) {
                $query->where('email', $request->email);
            }
            if ($request->phone) {
                $query->orWhere('phone', $request->phone);
            }
        })->first();

        if (!$user) {
             abort(400, "There is no account linked to the given credentials.");
        }

        if (!$user->status) {
             abort(400, "You cannot log in with a disabled account.");
        }

        return $user;
    }

    
    public function register(Request $request)
    {
        DB::beginTransaction();
        try {

            $validator = Validator::make($request->all(),[
                'name' => 'required|string|max:255',
                'email' => 'nullable|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
                'password' => 'required_with:email|string|min:8|confirmed',
                'password_confirmation' => 'required_with:password',
                'country_code' => 'required',
                'phone' => 'required|min:9|unique:users,phone,NULL,id,deleted_at,NULL',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'country_code' => $request->country_code,
                'phone'  => (string) $request->phone,
            ]);

            $user->assignRole(RoleEnum::CONSUMER);
            if (Helpers::pointIsEnable()) {
                $settings = Helpers::getSettings();
                $signUpPoints = $settings['wallet_points']['signup_points'];
                $this->creditPoints($user->id, $signUpPoints, WalletPointsDetail::SIGN_UP_BONUS);
                event(new SignUpBonusPointsEvent($user));
                $user->point;
            }

            if (Helpers::walletIsEnable()) {
                $user->wallet()->create();
                $user->wallet;
            }

            DB::commit();
            return [
                'access_token' =>  $user->createToken('auth_token')->plainTextToken,
                'permissions'  =>  $user->getPermissionNames(),
                'success' => true
            ];

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    
    public function forgotPassword(Request $request)
    {
        try {

            $validator = Validator::make($request->all(),[
                'email' => 'required|email|exists:users',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $token = rand(11111, 99999);
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);

            Mail::to($request->email)->send(new ForgotPassword($token));
            return [
                'message' => "We have e-mailed verification code in registered mail!",
                'success' => true
            ];

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function verifyToken(Request $request)
    {
        try {

            $validator = Validator::make($request->all(),[
                'token' => 'required',
                'email' => 'required|email|max:255',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $user =  DB::table('password_resets')
                    ->where('token',$request->token)
                    ->where('email',$request->email)
                    ->where('created_at','>',Carbon::now()->subHours(1))
                    ->first();

            if (!$user) {
                throw new Exception('The provided email or token is not recognized.', 400);
            }

            return [
                'message' => "Verification token has been successfully verified.",
                'success' => true
            ];

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function updatePassword(Request $request)
    {
        DB::beginTransaction();
        try {

            $validator = Validator::make($request->all(),[
                'token' => 'required',
                'email' => 'required|email|max:255|exists:users',
                'password' => 'required|min:8|confirmed',
                'password_confirmation' => 'required'
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $user =  DB::table('password_resets')
                ->where('token',$request->token)
                ->where('email',$request->email)
                ->where('created_at','>',Carbon::now()->subHours(1))
                ->first();

            if (!$user) {
                throw new Exception('The provided email or token is not recognized.', 400);
            }

            User::where('email',$request->email)
                ->update(['password' => Hash::make($request->password)]);

            DB::table('password_resets')->where('email',$request->email)->delete();
            DB::commit();

            return [
                'message' => "Your password has been successfully changed!",
                'success' => true
            ];

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *      path="/logout",
     *      operationId="logout",
     *      tags={"Authentication"},
     *      summary="User Logout",
     *      description="Logout current user and revoke token",
     *      security={{"sanctum":{}}},
     *      @OA\Response(response=200, description="Successful logout"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request)
    {
        // Safely check for token and delete if exists
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return [
            'message' => "You are all logged out! We hope to see you soon again.",
            'success' => true
        ];
    }

    public function sendOtp(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Log::info("OTP Send Request Received:", $request->all());

            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'country_code' => 'nullable',
                'name' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            // 1. Delete any existing OTPs for this phone
            \App\Models\OtpCode::where('phone', $request->phone)->delete();

            // 2. Generate random 6-digit OTP
            $otp = (string) rand(100000, 999999);

            // 3. Store hashed OTP in otp_codes with expires_at = now + 5 minutes
            \App\Models\OtpCode::create([
                'phone' => $request->phone,
                'country_code' => $request->country_code ?? '+91',
                'name' => $request->name,
                'otp' => Hash::make($otp),
                'expires_at' => Carbon::now()->addMinutes(5),
            ]);

            // 4. Send SMS via Spring Edge
            $this->smsService->sendOtp($request->phone, $otp);

            return [
                'message' => 'OTP has been sent successfully to your mobile number.',
                'success' => true
            ];

        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Log::info("OTP Verification Request Received:", $request->all());

            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'otp' => 'required',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            // 1. Look up otp_codes where phone matches and expires_at > now()
            $records = \App\Models\OtpCode::where('phone', $request->phone)
                                         ->where('expires_at', '>', Carbon::now())
                                         ->get();

            $matchedRecord = null;
            // 2. Verify with Hash::check
            foreach ($records as $record) {
                if (Hash::check($request->otp, $record->otp)) {
                    $matchedRecord = $record;
                    break;
                }
            }

            if (!$matchedRecord) {
                throw new Exception("Invalid or expired OTP code.", 400);
            }

            // 3. If valid: delete the OTP record(s)
            \App\Models\OtpCode::where('phone', $request->phone)->delete();

            // Find user by phone or create them
            $user = User::where('phone', $request->phone)->first();

            if (!$user) {
                $name = $matchedRecord->name ?? 'User ' . substr($request->phone, -4);
                $email = $request->phone . '@app.com';

                $user = User::create([
                    'name' => $name,
                    'phone' => $request->phone,
                    'email' => $email,
                    'country_code' => $matchedRecord->country_code ?? '+91',
                    'status' => 1,
                ]);

                $user->assignRole(RoleEnum::CONSUMER);

                if (Helpers::pointIsEnable()) {
                    $settings = Helpers::getSettings();
                    $signUpPoints = $settings['wallet_points']['signup_points'];
                    $this->creditPoints($user->id, $signUpPoints, WalletPointsDetail::SIGN_UP_BONUS);
                    event(new SignUpBonusPointsEvent($user));
                }

                if (Helpers::walletIsEnable()) {
                    $user->wallet()->create();
                }
            }

            // Issue Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;
            $user->tokens()->orderBy('id', 'desc')->first()->update([
                'role_type' => $user->getRoleNames()->first()
            ]);

            return [
                'success' => true,
                'access_token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ],
                'permissions' => $user->getAllPermissions(),
            ];

        } catch (Exception $e) {
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }
}
