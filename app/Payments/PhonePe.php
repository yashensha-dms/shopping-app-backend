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

class PhonePe
{
  use PaymentTrait, TransactionsTrait;

  protected static function getPaymentUrl(): string
  {
    if (env('PHONEPE_SANDBOX_MODE')) {
      return 'https://api-preprod.phonepe.com/apis/hermes';
    }
    return 'https://api.phonepe.com/apis/hermes';
  }

  /**
   * Detect if the request is from the mobile app.
   * The mobile app should pass source=mobile or X-App-Source: mobile header.
   */
  protected static function isMobileRequest($request): bool
  {
    // Check explicit source/device_type parameter in request body
    if (!empty($request->source) && strtolower($request->source) === 'mobile') {
      return true;
    }
    if (!empty($request->device_type) && in_array(strtolower($request->device_type), ['android', 'ios', 'mobile'])) {
      return true;
    }

    // Check request header X-App-Source
    $appSource = request()->header('X-App-Source', '');
    if (strtolower($appSource) === 'mobile') {
      return true;
    }

    // Fallback: detect from User-Agent (React Native apps have specific UA)
    $ua = strtolower(request()->header('User-Agent', ''));
    if (
      str_contains($ua, 'okhttp') ||         // Android React Native default
      str_contains($ua, 'expo') ||           // Expo
      str_contains($ua, 'cfnetwork') ||      // iOS native
      str_contains($ua, 'darwinmobile') ||   // iOS native
      str_contains($ua, 'react-native')      // RN explicit
    ) {
      return true;
    }

    return false;
  }

  /**
   * Fetch OAuth access token for PhonePe PG v2.
   * Caches for 55 minutes (tokens expire in 60 min).
   */
  protected static function getAccessToken(): ?string
  {
    $clientId     = env('PHONEPE_CLIENT_ID');
    $clientSecret = env('PHONEPE_CLIENT_SECRET');
    $clientVersion = (int)env('PHONEPE_CLIENT_VERSION', 1);

    if (empty($clientId) || empty($clientSecret)) {
      return null;
    }

    $cacheKey = 'phonepe_access_token_' . md5($clientId);
    if (Cache::has($cacheKey)) {
      return Cache::get($cacheKey);
    }

    $url = env('PHONEPE_SANDBOX_MODE')
      ? 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token'
      : 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token';

    $payload = http_build_query([
      'grant_type'     => 'client_credentials',
      'client_id'      => $clientId,
      'client_secret'  => $clientSecret,
      'client_version' => $clientVersion,
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL            => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 15,
      CURLOPT_CUSTOMREQUEST  => "POST",
      CURLOPT_POSTFIELDS     => $payload,
      CURLOPT_HTTPHEADER     => [
        "Content-Type: application/x-www-form-urlencoded",
        "accept: application/json",
      ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $decoded = json_decode($response, true);
    $token   = $decoded['access_token'] ?? null;

    if ($token) {
      $expiresIn = $decoded['expires_in'] ?? 3600;
      Cache::put($cacheKey, $token, $expiresIn - 300);
      return $token;
    }

    \Illuminate\Support\Facades\Log::error("PhonePe OAuth Failed to get access_token", [
      'url'      => $url,
      'response' => $response,
      'decoded'  => $decoded,
      'clientId' => $clientId,
    ]);

    return null;
  }

  public static function getIntent(Order $order, $request)
  {
    try {

      $transaction_id = uniqid();
      $merchantId     = trim((string)env('PHONEPE_MERCHANT_ID', env('PHONEPE_CLIENT_ID')));
      $baseUrl        = rtrim(config('app.url', 'https://mstore.primeads.ai'), '/');

      $redirectUrl = !empty($request->return_url)
        ? $request->return_url . '/' . $order->order_number
        : $baseUrl . '/account/order/details/' . $order->order_number;

      $callbackUrl = !empty($request->cancel_url)
        ? $request->cancel_url . '/' . $order->order_number
        : $baseUrl . '/account/order/details/' . $order->order_number;

      if (!preg_match("~^(?:f|ht)tps?://~i", $redirectUrl)) {
        $redirectUrl = $baseUrl . '/' . ltrim($redirectUrl, '/');
      }
      if (!preg_match("~^(?:f|ht)tps?://~i", $callbackUrl)) {
        $callbackUrl = $baseUrl . '/' . ltrim($callbackUrl, '/');
      }

      $token  = self::getAccessToken();
      $amount = (int)round(Helpers::convertToINR($order?->total) * 100);
      $isMobile = self::isMobileRequest($request);

      if ($token) {
        // ─────────────────────────────────────────────────────────────────
        // PhonePe PG v2 — OAuth "O-Bearer", PG_CHECKOUT hosted page
        //
        // IMPORTANT: PhonePe v2 only supports PG_CHECKOUT flow.
        // UPI apps / QR code are shown automatically by PhonePe's hosted
        // page when opened in the device's NATIVE BROWSER (not WebView).
        //
        // Mobile app must use: Linking.openURL(url)  — NOT a WebView.
        // ─────────────────────────────────────────────────────────────────

        $v2Payload = [
          'merchantOrderId' => $transaction_id,
          'amount'          => $amount,
          'expireAfter'     => 1800,
          'paymentFlow'     => [
            'type'         => 'PG_CHECKOUT',
            'message'      => 'Order #' . $order->order_number,
            'merchantUrls' => [
              'redirectUrl' => $redirectUrl,
            ],
          ],
        ];

        if (!empty($order?->consumer?->phone)) {
          $v2Payload['metaInfo']['customerMobile'] = (string)$order->consumer->phone;
        }

        $payUrl = env('PHONEPE_SANDBOX_MODE')
          ? 'https://api-preprod.phonepe.com/apis/pg/checkout/v2/pay'
          : 'https://api.phonepe.com/apis/pg/checkout/v2/pay';

        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL            => $payUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_CUSTOMREQUEST  => "POST",
          CURLOPT_POSTFIELDS     => json_encode($v2Payload, JSON_UNESCAPED_SLASHES),
          CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "accept: application/json",
            "Authorization: O-Bearer " . $token,
          ],
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if (!empty($err)) {
          throw new Exception($err, 500);
        }

        $res = json_decode($response, true);
        \Illuminate\Support\Facades\Log::info("PhonePe v2 Response", ['response' => $response]);

        // Returns the PhonePe hosted checkout page URL.
        // Mobile app: use Linking.openURL(url) to open in native browser.
        // UPI apps (GPay, PhonePe, Paytm) will appear automatically there.
        $paymentUrl = $res['redirectUrl'] ?? $res['checkoutUrl'] ?? null;

        if ($paymentUrl) {
          if (!self::verifyOrderTransaction($order?->id, $transaction_id)) {
            self::storeOrderTransaction($order, $transaction_id, $request->payment_method);
          }
          return [
            'order_number'   => $order->order_number,
            'url'            => $paymentUrl,
            'transaction_id' => $transaction_id,
            'is_redirect'    => true,
            'flow'           => 'pg_checkout',
          ];
        }

        $msg = $res['message'] ?? ($res['error'] ?? 'PhonePe v2 initiation failed');
        \Illuminate\Support\Facades\Log::error("PhonePe v2 Initiation Failed", [
          'raw_response' => $response,
          'payload'      => $v2Payload,
          'has_token'    => true,
        ]);
        throw new Exception($msg, 400);

      } else {
        // ─────────────────────────────────────────────────────────────────
        // PhonePe v1 Legacy — SHA256 X-VERIFY header, base64 body
        // ─────────────────────────────────────────────────────────────────
        $saltKey   = env('PHONEPE_SALT_KEY', env('PHONEPE_CLIENT_SECRET'));
        $saltIndex = env('PHONEPE_SALT_INDEX', '1');

        // Mobile → UPI_INTENT, Web → PAY_PAGE
        $instrumentType = $isMobile ? 'UPI_INTENT' : 'PAY_PAGE';

        $intent = [
          'merchantId'            => $merchantId,
          'merchantTransactionId' => $transaction_id,
          'merchantUserId'        => (string)($order?->consumer_id ?? 'GUEST'),
          'amount'                => $amount,
          'redirectUrl'           => $redirectUrl,
          'redirectMode'          => 'REDIRECT',
          'callbackUrl'           => $callbackUrl,
          'paymentInstrument'     => ['type' => $instrumentType],
        ];

        if (!empty($order?->consumer?->phone)) {
          $intent['mobileNumber'] = (string)$order->consumer->phone;
        }

        $payloadMain = base64_encode(json_encode($intent, JSON_UNESCAPED_SLASHES));
        $sha256      = hash('sha256', $payloadMain . '/pg/v1/pay' . $saltKey);
        $x_header    = $sha256 . '###' . $saltIndex;

        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL            => self::getPaymentUrl() . "/pg/v1/pay",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_CUSTOMREQUEST  => "POST",
          CURLOPT_POSTFIELDS     => json_encode(['request' => $payloadMain]),
          CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "accept: application/json",
            "X-VERIFY: " . $x_header,
          ],
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        if (!empty($err)) {
          throw new Exception($err, 500);
        }

        $res = json_decode($response);
        if (isset($res->success) && ($res->success == '1' || $res->success === true)) {
          // UPI_INTENT returns intentUrl; PAY_PAGE returns redirect page URL
          $paymentUrl = $res?->data?->instrumentResponse?->intentUrl    // upi://pay?...
            ?? $res?->data?->instrumentResponse?->redirectInfo?->url    // PAY_PAGE
            ?? $res?->data?->redirectUrl;

          if (!self::verifyOrderTransaction($order?->id, $transaction_id)) {
            self::storeOrderTransaction($order, $transaction_id, $request->payment_method);
          }
          return [
            'order_number'   => $order->order_number,
            'url'            => $paymentUrl,
            'transaction_id' => $transaction_id,
            'is_redirect'    => true,
            'flow'           => strtolower($instrumentType),
          ];
        }

        $msg = $res->message ?? 'PhonePe initiation failed';
        \Illuminate\Support\Facades\Log::error("PhonePe v1 Initiation Failed", [
          'raw_response' => $response,
          'payload'      => $intent,
          'merchantId'   => $merchantId,
        ]);
        throw new Exception($msg, 400);
      }

    } catch (Exception $e) {
      \Illuminate\Support\Facades\Log::error("PhonePe Exception: " . $e->getMessage());
      self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
    }
  }

  public static function status(Order $order, $transaction_id)
  {
    try {
      $merchantId = trim((string)env('PHONEPE_MERCHANT_ID', env('PHONEPE_CLIENT_ID')));
      $token      = self::getAccessToken();

      if ($token) {
        $statusUrl = env('PHONEPE_SANDBOX_MODE')
          ? 'https://api-preprod.phonepe.com/apis/pg/checkout/v2/order/' . $transaction_id . '/status'
          : 'https://api.phonepe.com/apis/pg/checkout/v2/order/' . $transaction_id . '/status';

        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL            => $statusUrl,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_CUSTOMREQUEST  => "GET",
          CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: O-Bearer " . $token,
          ],
        ]);
        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        $resData  = json_decode($response, true);
        $state    = $resData['state'] ?? '';
        $payState = $resData['paymentDetails'][0]['state'] ?? '';

        if ($state === 'COMPLETED' || $payState === 'COMPLETED') {
          return self::updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
        } else if (!empty($err)) {
          throw new Exception($err, 500);
        }
        return $order;

      } else {
        $saltKey   = env('PHONEPE_SALT_KEY', env('PHONEPE_CLIENT_SECRET'));
        $saltIndex = env('PHONEPE_SALT_INDEX', '1');
        $x_header  = hash('sha256', '/pg/v1/status/' . $merchantId . "/{$transaction_id}" . $saltKey) . '###' . $saltIndex;

        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL            => self::getPaymentUrl() . "/pg/v1/status/" . $merchantId . '/' . $transaction_id,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_TIMEOUT        => 30,
          CURLOPT_CUSTOMREQUEST  => "GET",
          CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "X-VERIFY: " . $x_header,
            "X-MERCHANT-ID: " . $merchantId,
          ],
        ]);

        $response = curl_exec($curl);
        $err      = curl_error($curl);
        curl_close($curl);

        $resData = json_decode($response, true);
        if (isset($resData['code']) && $resData['code'] == "PAYMENT_SUCCESS") {
          return self::updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
        } else if (!empty($err)) {
          throw new Exception($err, 500);
        } else if (is_null($resData)) {
          return $order;
        }

        throw new Exception($resData['message'] ?? 'Payment status check failed', 400);
      }

    } catch (Exception $e) {
      self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
    }
  }
}
