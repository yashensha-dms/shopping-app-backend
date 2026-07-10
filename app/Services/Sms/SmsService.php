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
        $message = "Your Grabzo verification code is {$otp}. Valid for 5 minutes.";
        return $this->provider->send($phone, $message);
    }
}
