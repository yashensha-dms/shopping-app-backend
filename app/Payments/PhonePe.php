<?php

namespace App\Payments;

use Exception;
use App\Models\Order;
use App\Helpers\Helpers;
use App\Enums\PaymentStatus;
use App\Http\Traits\PaymentTrait;
use App\Http\Traits\TransactionsTrait;
use App\GraphQL\Exceptions\ExceptionHandler;
use PhonePe\payments\v2\standardCheckout\StandardCheckoutClient;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutPayRequestBuilder;
use PhonePe\payments\v2\models\request\builders\StandardCheckoutRefundRequestBuilder;
use PhonePe\common\exceptions\PhonePeException;
use PhonePe\Env;

class PhonePe
{
  use TransactionsTrait, PaymentTrait;

  /**
   * Returns an initialized StandardCheckoutClient singleton.
   * NOTE: PhonePe PHP SDK only supports Env::PRODUCTION.
   */
  protected static function getClient(): StandardCheckoutClient
  {
    $clientId      = env('PHONEPE_CLIENT_ID');
    $clientVersion = (int) env('PHONEPE_CLIENT_VERSION', 1);
    $clientSecret  = env('PHONEPE_CLIENT_SECRET');

    return StandardCheckoutClient::getInstance(
      $clientId,
      $clientVersion,
      $clientSecret,
      Env::PRODUCTION
    );
  }

  /**
   * Initiate a PhonePe payment for an order.
   * Returns the redirect URL to send the user to PhonePe checkout.
   */
  public static function getIntent(Order $order, $request)
  {
    try {
      $client = self::getClient();

      $transaction_id = uniqid();
      $baseUrl        = rtrim(config('app.url', 'https://mstore.primeads.ai'), '/');

      // Redirect URL after payment (success or failure)
      $redirectUrl = !empty($request->return_url)
        ? $request->return_url . '/' . $order->order_number
        : $baseUrl . '/account/order/details/' . $order->order_number;

      // Amount in paisa (₹1 = 100 paisa)
      $amount = (int) round(Helpers::convertToINR($order?->total) * 100);

      if ($amount < 100) {
        throw new Exception('Order amount must be at least ₹1.00 (100 paise) to initiate PhonePe payment.', 400);
      }

      // Build the pay request using the SDK builder
      $payRequest = StandardCheckoutPayRequestBuilder::builder()
        ->merchantOrderId($transaction_id)
        ->amount($amount)
        ->redirectUrl($redirectUrl)
        ->message('Order #' . $order->order_number)
        ->build();

      // Call PhonePe API
      $payResponse = $client->pay($payRequest);

      if ($payResponse->getState() === 'PENDING') {
        // Store transaction before redirecting
        if (!self::verifyOrderTransaction($order?->id, $transaction_id)) {
          self::storeOrderTransaction($order, $transaction_id, $request->payment_method);
        }

        return [
          'order_number'   => $order->order_number,
          'url'            => $payResponse->getRedirectUrl(),
          'transaction_id' => $transaction_id,
          'is_redirect'    => true,
        ];
      }

      throw new Exception('PhonePe payment initiation failed. State: ' . $payResponse->getState(), 400);

    } catch (PhonePeException $e) {
      \Illuminate\Support\Facades\Log::error('PhonePe SDK Exception (pay): ' . $e->getMessage(), [
        'http_status' => $e->getHttpStatusCode(),
      ]);
      self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      throw new ExceptionHandler($e->getMessage(), $e->getHttpStatusCode() ?: 500);

    } catch (Exception $e) {
      \Illuminate\Support\Facades\Log::error('PhonePe Exception (pay): ' . $e->getMessage());
      self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
    }
  }

  /**
   * Check order payment status from PhonePe.
   * Called after user returns from PhonePe checkout page.
   */
  public static function status(Order $order, $transaction_id)
  {
    try {
      $client = self::getClient();

      // false = return only latest payment attempt
      $statusResponse = $client->getOrderStatus($transaction_id, false);
      $state          = $statusResponse->getState();

      if ($state === 'COMPLETED') {
        return self::updateOrderPaymentStatus($order, PaymentStatus::COMPLETED);
      }

      if ($state === 'FAILED') {
        return self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      }

      // PENDING — payment still in progress
      return $order;

    } catch (PhonePeException $e) {
      \Illuminate\Support\Facades\Log::error('PhonePe SDK Exception (status): ' . $e->getMessage(), [
        'http_status'    => $e->getHttpStatusCode(),
        'transaction_id' => $transaction_id,
      ]);
      self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      throw new ExceptionHandler($e->getMessage(), $e->getHttpStatusCode() ?: 500);

    } catch (Exception $e) {
      self::updateOrderPaymentStatus($order, PaymentStatus::FAILED);
      throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
    }
  }

  /**
   * Initiate a refund for an order.
   * $amount in paisa — if null, refunds full order amount.
   */
  public static function refund(Order $order, ?int $amount = null)
  {
    try {
      $client = self::getClient();

      $merchantRefundId = 'REFUND_' . uniqid();
      $refundAmount     = $amount ?? (int) round(Helpers::convertToINR($order?->total) * 100);

      // Retrieve the original transaction_id stored during payment
      $transaction = $order->transactions()->latest()->first();
      if (!$transaction) {
        throw new Exception('No transaction found for order #' . $order->order_number, 400);
      }

      $refundRequest = StandardCheckoutRefundRequestBuilder::builder()
        ->merchantRefundId($merchantRefundId)
        ->originalMerchantOrderId($transaction->transaction_id)
        ->amount($refundAmount)
        ->build();

      $refundResponse = $client->refund($refundRequest);

      return [
        'refund_id' => $refundResponse->getRefundId(),
        'state'     => $refundResponse->getState(),
        'amount'    => $refundResponse->getAmount(),
      ];

    } catch (PhonePeException $e) {
      \Illuminate\Support\Facades\Log::error('PhonePe SDK Exception (refund): ' . $e->getMessage(), [
        'http_status' => $e->getHttpStatusCode(),
        'order'       => $order->order_number,
      ]);
      throw new ExceptionHandler($e->getMessage(), $e->getHttpStatusCode() ?: 500);

    } catch (Exception $e) {
      throw new ExceptionHandler($e->getMessage(), $e->getCode() ?: 500);
    }
  }

  /**
   * Verify and process an incoming PhonePe webhook/callback.
   * Set PHONEPE_WEBHOOK_USERNAME and PHONEPE_WEBHOOK_PASSWORD in .env
   * (configured in PhonePe Business Dashboard → Developer Settings → Webhook).
   */
  public static function handleWebhook()
  {
    try {
      $client = self::getClient();

      $headers     = function_exists('getallheaders') ? getallheaders() : request()->headers->all();
      $requestBody = request()->getContent();
      if (empty($requestBody)) {
        $requestBody = file_get_contents('php://input');
      }
      $username    = env('PHONEPE_WEBHOOK_USERNAME', '');
      $password    = env('PHONEPE_WEBHOOK_PASSWORD', '');

      $callbackResponse = $client->verifyCallbackResponse(
        $headers,
        $requestBody,
        $username,
        $password
      );

      return $callbackResponse;

    } catch (PhonePeException $e) {
      \Illuminate\Support\Facades\Log::error('PhonePe Webhook Verification Failed: ' . $e->getMessage());
      throw new ExceptionHandler($e->getMessage(), $e->getHttpStatusCode() ?: 400);
    }
  }
}
