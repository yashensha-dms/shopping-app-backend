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

    public function login(Request $request)
    {
        try {

            $user = $this->verifyLogin($request);
            if (!Hash::check($request->password, $user->password) || !$user->hasRole(RoleEnum::CONSUMER)) {
                throw new Exception('The entered credentials are incorrect, Please try Again!', 400);
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

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
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
        try {

            $user = $this->verifyLogin($request);
            if (!Hash::check($request->password, $user->password) || $user->hasRole(RoleEnum::CONSUMER)) {
                throw new Exception('The entered backend credentials are incorrect, Please try Again!', 400);
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

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function verifyLogin(Request $request)
    {
        try {

            $validator = Validator::make($request->all(),[
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            if ($validator->fails()) {
                throw new Exception($validator->messages()->first(), 422);
            }

            $user = User::where('email',$request->email)->first();
            if (!$user) {
                throw new Exception("There is no account linked to the given email.", 400);
            }

            if (!$user->status) {
                throw new Exception("You cannot log in with a disabled account.", 400);
            }

            return $user;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

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
        try {

            $token = PersonalAccessToken::findToken($request->bearerToken());
            if(!$token) {
                throw new Exception('The provided access token is not valid. Please try again.', 400);
            }

            $token->delete();
            return [
                'message' => "You are all logged out! We hope to see you soon again.",
                'success' => true
            ];

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }
}
