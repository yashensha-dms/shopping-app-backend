<?php

namespace App\Payments;

use Exception;
use App\Models\Order;
use App\Helpers\Helpers;
use App\Enums\PaymentStatus;
use App\Http\Traits\PaymentTrait;
use App\Http\Traits\TransactionsTrait;
use App\GraphQL\Exceptions\ExceptionHandler;
use Illuminate\Support\Facades\Cache;

class PhonePe {

  use TransactionsTrait, PaymentTrait;

  public static function getPaymentUrl()
  {
    $payment_base_url = 'https://api.phonepe.com/apis/hermes';
    if (env('PHONEPE_SANDBOX_MODE')) {
      $payment_base_url = 'https://api-preprod.phonepe.com/apis/pg-sandbox';
    }

    return $payment_base_url;
  }

  /**
   * Fetch PhonePe OAuth v2 Token if Client ID & Secret are configured
   */
  public static function getAccessToken()
  {
    $clientId = env('PHONEPE_CLIENT_ID');
    $clientSecret = env('PHONEPE_CLIENT_SECRET');
    $clientVersion = env('PHONEPE_CLIENT_VERSION', '1');

    if (empty($clientId) || empty($clientSecret)) {
      return null;
    }

    $cacheKey = 'phonepe_oauth_token_' . md5($clientId);
    if (Cache::has($cacheKey)) {
      return Cache::get($cacheKey);
    }

    $url = self::getPaymentUrl() . '/v1/oauth/token';
    $payload = http_build_query([
      'grant_type' => 'client_credentials',
      'client_id' => $clientId,
      'client_secret' => $clientSecret,
      'client_version' => $clientVersion,
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $payload,
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/x-www-form-urlencoded",
        "accept: application/json"
      ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
      throw new Exception("PhonePe OAuth Error: " . $err, 500);
    }

    $res = json_decode($response, true);
    if (isset($res['access_token'])) {
      $expiresIn = $res['expires_in'] ?? 3600;
      Cache::put($cacheKey, $res['access_token'], now()->addSeconds($expiresIn - 60));
      return $res['access_token'];
    }

    return null;
  }

  public static function getIntent(Order $order, $request)
  {
    try {

      $transaction_id = uniqid();
      $merchantId = env('PHONEPE_MERCHANT_ID', env('PHONEPE_CLIENT_ID'));

      $intent = [
        'merchantId' => $merchantId,
        'merchantTransactionId' => $transaction_id,
        'merchantUserId' => (string)($order?->consumer_id ?? 'GUEST'),
        'merchantOrderId' => (string)$order->order_number,
        'amount' => (int)round(Helpers::convertToINR($order?->total) * 100),
        'redirectUrl' => $request->return_url . '/' . $order->order_number,
        'callbackUrl' => $request->cancel_url . '/' . $order->order_number,
        'mobileNumber' => (string)($order?->consumer?->phone ?? ''),
        'redirectMode' => 'POST',
        'paymentInstrument' => [
          'type' => 'PAY_PAGE'
        ]
      ];

      $payloadMain = base64_encode(json_encode($intent));
      $token = self::getAccessToken();

      $headers = [
        "Content-Type: application/json",
        "accept: application/json"
      ];

      if ($token) {
        // PhonePe PG v2 OAuth Header
        $headers[] = "Authorization: Bearer " . $token;
        $headers[] = "X-MERCHANT-ID: " . $merchantId;
      } else {
        // Legacy v1 SHA256 Checksum Header Fallback
        $string = $payloadMain . '/pg/v1/pay' . env('PHONEPE_SALT_KEY');
        $sha256 = hash('sha256', $string);
        $x_header = $sha256 . '###' . env('PHONEPE_SALT_INDEX');
        $headers[] = "X-VERIFY: " . $x_header;
      }

      $intentPayload = json_encode(['request' => $payloadMain]);

      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => self::getPaymentUrl() . "/pg/v1/pay",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $intentPayload,
        CURLOPT_HTTPHEADER => $headers,
      ]);

      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      if (!is_null($err) && !empty($err)) {
        throw new Exception($err, 500);
      } else {
        $res = json_decode($response);
        if (isset($res->success) && ($res->success == '1' || $res->success === true)) {
          $paymentUrl = $res?->data?->instrumentResponse?->redirectInfo->url;
          if (!self::verifyOrderTransaction($order?->id, $transaction_id)) {
            self::storeOrderTransaction($order, $transaction_id, $request->payment_method);
          }

          return [
            'order_number' => $order->order_number,
            'url' => $paymentUrl,
            'transaction_id' => $transaction_id,
            'is_redirect' => true,
          ];
        } else {
          $msg = $res->message ?? 'PhonePe initiation failed';
          throw new Exception($msg, 400);
        }
      }

    } catch (Exception $e) {
      self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
    }
  }

  public static function status(Order $order, $transaction_id)
  {
    try {
      $merchantId = env('PHONEPE_MERCHANT_ID', env('PHONEPE_CLIENT_ID'));
      $token = self::getAccessToken();

      $headers = [
        "Content-Type: application/json",
        "X-MERCHANT-ID: " . $merchantId,
      ];

      if ($token) {
        $headers[] = "Authorization: Bearer " . $token;
      } else {
        $x_header = hash('sha256', '/pg/v1/status/' . $merchantId . "/{$transaction_id}" . env('PHONEPE_SALT_KEY')) . '###' . env('PHONEPE_SALT_INDEX');
        $headers[] = "X-VERIFY: " . $x_header;
      }

      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => self::getPaymentUrl() . "/pg/v1/status/" . $merchantId . '/' . $transaction_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => $headers,
      ]);

      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      $resData = json_decode($response, true);

      if (isset($resData['code']) && $resData['code'] == "PAYMENT_SUCCESS") {
        return self::updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
      } else if (isset($err) && !empty($err)) {
        throw new Exception($err, 500);
      } else if (is_null($resData) || empty($err)) {
        return $order;
      }

      $msg = $resData['message'] ?? 'Payment status check failed';
      throw new Exception($msg, 400);

    } catch (Exception $e) {
      self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
    }
  }
}

