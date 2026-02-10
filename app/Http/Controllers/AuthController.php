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
        if (!Hash::check($request->password, $user->password) || !$user->hasRole(RoleEnum::CONSUMER)) {
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

    public function backendLogin(Request $request)
    {
        $user = $this->verifyLogin($request);
        if (!Hash::check($request->password, $user->password) || $user->hasRole(RoleEnum::CONSUMER)) {
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
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        $user = User::where('email',$request->email)->first();
        if (!$user) {
            // Throwing a generic error to prevent email enumeration, or specific if desired.
            // Using standard exception for now but catchable upstream if needed.
            // However, previous code threw 400.
             abort(400, "There is no account linked to the given email.");
        }

        if (!$user->status) {
             abort(400, "You cannot log in with a disabled account.");
        }

        return $user;
    }

    /**
     * @OA\Post(
     *      path="/register",
     *      operationId="register",
     *      tags={"Authentication"},
     *      summary="User Registration",
     *      description="Register a new user account",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email","password","password_confirmation","country_code","phone"},
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="password123"),
     *              @OA\Property(property="password_confirmation", type="string", example="password123"),
     *              @OA\Property(property="country_code", type="string", example="+1"),
     *              @OA\Property(property="phone", type="string", example="1234567890")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful registration",
     *          @OA\JsonContent(
     *              @OA\Property(property="access_token", type="string"),
     *              @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
     *              @OA\Property(property="success", type="boolean", example=true)
     *          )
     *      ),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        DB::beginTransaction();
        try {

            $validator = Validator::make($request->all(),[
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,NULL,id,deleted_at,NULL',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required',
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

    /**
     * @OA\Post(
     *      path="/forgot-password",
     *      operationId="forgotPassword",
     *      tags={"Authentication"},
     *      summary="Forgot Password",
     *      description="Send password reset token to email",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email"},
     *              @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Verification code sent"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
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
}
