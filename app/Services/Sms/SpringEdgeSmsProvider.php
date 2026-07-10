<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpringEdgeSmsProvider implements SmsProviderInterface
{
    public function send(string $phone, string $message): bool
    {
        // Support both configurations (with and without underscores)
        $apiKey = env('SPRING_EDGE_API_KEY') ?: env('SPRINGEDGE_API_KEY');
        $sender = env('SPRING_EDGE_SENDER') ?: env('SPRINGEDGE_SENDER');
        $dltTemplateId = env('SPRING_EDGE_DLT_TEMPLATE_ID') ?: env('SPRINGEDGE_DLT_TEMPLATE_ID');
        $dltEntityId = env('SPRING_EDGE_DLT_ENTITY_ID') ?: env('SPRINGEDGE_DLT_ENTITY_ID');

        // Fallback to log provider if not configured yet
        if (empty($apiKey) || empty($sender)) {
            Log::warning("Spring Edge SMS not configured. Logging message: " . $message);
            $logPath = storage_path('logs/otps.txt');
            $timestamp = now()->toDateTimeString();
            file_put_contents($logPath, "[{$timestamp}] (Spring Edge Fallback) SMS to {$phone}: {$message}" . PHP_EOL, FILE_APPEND);
            return true;
        }

        try {
            // Spring Edge endpoint specified in production plan
            $url = 'https://instantalerts.co/api/web/send';
            
            // Format phone to prefix with 91 if it doesn't already have it
            $formattedPhone = $phone;
            if (!str_starts_with($formattedPhone, '91') && strlen($formattedPhone) == 10) {
                $formattedPhone = '91' . $formattedPhone;
            }

            $params = [
                'apikey'  => $apiKey,
                'sender'  => $sender,
                'to'      => $formattedPhone,
                'message' => $message,
                'format'  => 'json'
            ];

            // If DLT Template ID is provided, include it in the request
            if (!empty($dltTemplateId)) {
                $params['dlttemplateid'] = $dltTemplateId;
                $params['header_id'] = $dltTemplateId;
            }

            // If DLT Entity ID is provided, include it in the request
            if (!empty($dltEntityId)) {
                $params['dltentityid'] = $dltEntityId;
                $params['entity_id'] = $dltEntityId;
            }

            $response = Http::timeout(10)->get($url, $params);

            if ($response->successful()) {
                Log::info("SMS successfully sent via Spring Edge to " . $formattedPhone);
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
