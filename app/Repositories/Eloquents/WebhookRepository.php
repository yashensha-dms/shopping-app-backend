<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\Order;
use App\Payments\Mollie;
use App\Payments\PayPal;
use App\Payments\PhonePe;
use App\Payments\Stripe;
use App\Payments\CCAvenue;
use App\Payments\RazorPay;
use App\Payments\InstaMojo;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;

class WebhookRepository extends BaseRepository
{
    function model()
    {
        return Order::class;
    }

    public function paypal($request)
    {
        try {

            return PayPal::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function stripe($request)
    {
        try {

            return Stripe::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function mollie($request)
    {
        try {

            return Mollie::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function razorpay($request)
    {
        try {

            return RazorPay::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function instamojo($request)
    {
        try {

            return InstaMojo::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function ccavenue($request)
    {
        try {

            return CCAvenue::webhookHandler($request);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function phonepe($request)
    {
        try {

            // Verify and process the PhonePe callback using the SDK
            $callbackResponse = PhonePe::handleWebhook();

            $payload = $callbackResponse->getPayload();
            $type    = $callbackResponse->getType();

            // Find the order by the merchantOrderId stored as transaction_id
            $merchantOrderId = $payload->getOriginalMerchantOrderId()
              ?? $payload->getMerchantOrderId()
              ?? null;

            if (!$merchantOrderId) {
                return response()->json(['status' => 'ignored'], 200);
            }

            $order = Order::whereHas('transactions', function ($q) use ($merchantOrderId) {
                $q->where('transaction_id', $merchantOrderId);
            })->first();

            if (!$order) {
                return response()->json(['status' => 'order_not_found'], 200);
            }

            // Update order status based on callback type
            if (in_array($type->value ?? $type, ['CHECKOUT_ORDER_COMPLETED', 'PG_REFUND_COMPLETED'])) {
                PhonePe::updateOrderPaymentStatus($order, \App\Enums\PaymentStatus::COMPLETED);
            } elseif (in_array($type->value ?? $type, ['CHECKOUT_ORDER_FAILED', 'PG_REFUND_FAILED'])) {
                PhonePe::updateOrderPaymentStatus($order, \App\Enums\PaymentStatus::FAILED);
            }

            return response()->json(['status' => 'ok'], 200);

        } catch (Exception $e) {

            \Illuminate\Support\Facades\Log::error('PhonePe Webhook Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
