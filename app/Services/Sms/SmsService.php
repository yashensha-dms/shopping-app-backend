<?php

namespace App\Services\Sms;

use App\Models\User;
use Carbon\Carbon;

class SmsService
{
    protected $provider;

    public function __construct(SmsProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function sendOtp(User $user)
    {
        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(5);
        $user->save();

        $message = "Your OTP for login is: {$otp}. It will expire in 5 minutes.";
        return $this->provider->send($user->phone ?? 'unknown', $message);
    }
}
