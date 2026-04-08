<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): bool
    {
        $logPath = storage_path('logs/otps.txt');
        $timestamp = now()->toDateTimeString();
        $logEntry = "[{$timestamp}] SMS to {$phone}: {$message}" . PHP_EOL;
        
        file_put_contents($logPath, $logEntry, FILE_APPEND);
        
        return true;
    }
}
