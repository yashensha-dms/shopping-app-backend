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

    public function sendOtp(string $phone, string $otp)
    {
        $message = "GRABZO BY MATHER welcomes you! Your OTP is {$otp}. Use it to log in. Valid for 10 minutes. Please do not share this code with anyone. Team Grabzo";
        return $this->provider->send($phone, $message);
    }
}
