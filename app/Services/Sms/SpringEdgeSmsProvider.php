<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpringEdgeSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): bool
    {
        $apiKey = env('SPRING_EDGE_API_KEY');
        $sender = env('SPRING_EDGE_SENDER');
        $dltTemplateId = env('SPRING_EDGE_DLT_TEMPLATE_ID');

        // Fallback to log provider if not configured yet
        if (empty($apiKey) || empty($sender)) {
            Log::warning("Spring Edge SMS not configured. Logging message: " . $message);
            $logPath = storage_path('logs/otps.txt');
            $timestamp = now()->toDateTimeString();
            file_put_contents($logPath, "[{$timestamp}] (Spring Edge Fallback) SMS to {$phone}: {$message}" . PHP_EOL, FILE_APPEND);
            return true;
        }

        try {
            // Spring Edge endpoint
            $url = 'https://web.springedge.com/web/api/send/';
            
            $params = [
                'apikey'  => $apiKey,
                'sender'  => $sender,
                'to'      => $phone,
                'message' => $message,
                'format'  => 'json'
            ];

            // If DLT Template ID is provided, include it in the request
            if (!empty($dltTemplateId)) {
                $params['dlttemplateid'] = $dltTemplateId;
            }

            $response = Http::get($url, $params);

            if ($response->successful()) {
                Log::info("SMS successfully sent via Spring Edge to " . $phone);
                return true;
            }

            Log::error("Spring Edge SMS sending failed. Status: " . $response->status() . " Body: " . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error("Spring Edge SMS sending exception: " . $e->getMessage());
            return false;
        }
    }
}
