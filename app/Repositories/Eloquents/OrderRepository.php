<?php

namespace App\Repositories\Eloquents;

use Exception;
use Carbon\Carbon;
use App\Models\Order;
use App\Payments\Cod;
use App\Payments\Mollie;
use App\Payments\PayPal;
use App\Payments\Stripe;
use App\Helpers\Helpers;
use App\Enums\OrderEnum;
use App\Payments\PhonePe;
use App\Payments\RazorPay;
use App\Payments\InstaMojo;
use Illuminate\Support\Arr;
use App\Models\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\PlaceOrderEvent;
use App\Models\OrderTransaction;
use App\Enums\WalletPointsDetail;
use App\Http\Traits\PaymentTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\CheckoutTrait;
use App\Http\Traits\TransactionsTrait;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Events\UpdateOrderStatusEvent;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Payments\CCAvenue;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;

class OrderRepository extends BaseRepository
{
    use CheckoutTrait, PaymentTrait, TransactionsTrait;

    protected $settings;
    protected $orderStatus;
    protected $orderTransaction;

    protected $fieldSearchable = [
        'order_number' => 'like',
        'payment_method' => 'like',
        'orderStatus.name' => 'like',
        'consumer.name' => 'like',
        'payment_status' => 'like',
    ];

    public function boot()
    {
        try {

            $this->pushCriteria(app(RequestCriteria::class));

        } catch (ExceptionHandler $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    function model()
    {
        $this->orderStatus = new OrderStatus();
        $this->settings = Helpers::getSettings();
        $this->orderTransaction = new OrderTransaction();
        return Order::class;
    }

    public function getOrderNumber($digits)
    {
        $i = 0;
        do {

            $order_number = pow(10, $digits) + $i++;

        } while ($this->model->where("order_number", "=", $order_number)->exists());

        return $order_number;
    }

    public function show($order_number)
    {
        try {

            $order = Helpers::getOrderByOrderNumber($order_number);
            if ($order) {
                return $this->verifyPayment($order);
            }

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function trackOrder($order_number)
    {
        try {

            return $this->show($order_number);

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function placeOrder($request)
    {
        DB::beginTransaction();
        try {

            $consumer_id = $this->getConsumerId($request);
            $products = $this->getUniqueProducts($request->products);
            $request->merge(['products' => $products]);
            $items = $this->calculate($request);

            if ($request->coupon) {
                $coupon = Helpers::getCoupon($request->coupon);
                $amount = Helpers::getTotalAmount($request->products);
                if ($this->isValidCoupon($coupon, $amount, $request->consumer_id)) {
                    $request->merge(['coupon_id' => $coupon->id]);
                }
            }

            $request->merge(['store_id' => head($items['items'])['store']]);
            $order = $this->createOrder($items, $request);
            if (Helpers::isMultiVendorEnable()) {
                $this->createSubOrder($items, $request, $order);
                $order->sub_orders;
            }

            DB::commit();
            Helpers::removeCart($order);

            $order = $order->fresh();
            if ($request->points_amount) {
                $balance = abs($items['total']['convert_point_amount']);
                if ($this->verifyPoints($consumer_id, $balance)) {
                    $balance = $this->currencyToPoints($balance);
                    $this->debitPoints($consumer_id, $balance, WalletPointsDetail::POINTS_ORDER.' #'.$order->order_number);
                }
            }

            if ($request->wallet_balance) {
                $balance = abs($items['total']['convert_wallet_balance']);
                if ($this->verifyWallet($consumer_id, $balance)) {
                    $this->debitWallet($consumer_id, $balance, WalletPointsDetail::WALLET_ORDER.' #'.$order->order_number);
                }
            }

            if (Helpers::pointIsEnable()) {
                $rewardPoints = $this->getRewardPoints($items['total']['total']);
                if ($rewardPoints) {
                    $this->creditPoints($consumer_id, $rewardPoints, WalletPointsDetail::REWARD.' #'.$order->order_number);
                }
            }

            if ($request->coupon_id) {
                $this->updateCouponUsage($request->coupon_id);
            }

            return $this->createPayment($order, $request);

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function createOrder($item, $request)
    {
        if ($this->isActivePaymentMethod($request->payment_method, $item['total']['total'])) {
            $order_number = (string) $this->getOrderNumber(3);
            if (!$request->points_amount) {
                $item['total']['convert_point_amount'] = 0;
            }
            if (!$request->wallet_balance) {
                $item['total']['convert_wallet_balance'] = 0;
            }

            $order =  $this->model->create([
                'order_number' => $order_number,
                'consumer_id' => $request->consumer_id ?? Helpers::getCurrentUserId(),
                'store_id' => $request->store_id,
                'tax_total' => $item['total']['tax_total'],
                'shipping_total' => $item['total']['shipping_total'],
                'payment_method' => $request->payment_method,
                'order_status_id' => Helpers::getOrderStatusIdByName(OrderEnum::PENDING),
                'shipping_address_id' => $request->shipping_address_id,
                'billing_address_id' =>  $request->billing_address_id,
                'delivery_description' => $request->delivery_description,
                'delivery_interval' => $request->delivery_interval,
                'parent_id' => $request->parent_id,
                'coupon_id' => $request->coupon_id,
                'points_amount' => $item['total']['convert_point_amount'],
                'wallet_balance' => $item['total']['convert_wallet_balance'],
                'invoice_url' => $this->generateInvoiceUrl($order_number),
                'coupon_total_discount' => $item['total']['coupon_total_discount'],
                'amount' => $item['total']['sub_total'],
                'total' => $item['total']['total']
            ]);

            if (!isset($item['products'])) {
                foreach($item['items'] as $itemValues) {
                    foreach ($itemValues['products'] as $productValue) {
                        $itemProduct[] = $productValue;
                    }
                }

                $item['products'] = $itemProduct;
            }

            foreach ($item['products'] as $itemProduct) {
                $itemProduct = Arr::except($itemProduct, ['store_id']);
                $item_products[] = $itemProduct;
            }

            $item['products'] = $item_products;
            $order->products()->attach($item['products']);
            $item_products = [];

            $order->store;
            $order->products;
            $order->order_status;

            event(new PlaceOrderEvent($order));
            return $order;
        }
    }

    public function createSubOrder($items, $request, Order $parentOrder)
    {
        $subOrders = [];
        if (count($items['items']) > 1) {
            foreach ($items['items'] as $item) {
                if (isset($request->products)){
                    $request->merge(['parent_id' => $parentOrder->id, 'store_id' => $item['store']]);
                    $order = $this->createOrder($item, $request);
                    $subOrders[] = $order;
                }
            }
        }

        return $subOrders;
    }

    public function getRewardPoints($total)
    {
        $minPerOrderAmount = $this->settings['wallet_points']['min_per_order_amount'];
        $rewardPerOrderAmount = $this->settings['wallet_points']['reward_per_order_amount'];
        if ($total >= $minPerOrderAmount) {
            $rewardPoints = ($total/$minPerOrderAmount)*$rewardPerOrderAmount;
            return $rewardPoints;
        }
    }

    public function getWalletRatio()
    {
        $walletRatio = $this->settings['general']['wallet_currency_ratio'];
        return $walletRatio <= 0 ? 1 : $walletRatio;
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {

            $request = Arr::except($request, ['order_number']);
            $order = $this->model->findOrFail($id);
            if (isset($request['order_status_id'])) {
                $order_status = $this->orderStatus->where('id', $request['order_status_id'])->pluck('name')->first();
                if ($order_status == OrderEnum::DELIVERED && $order->payment_method == PaymentMethod::COD) {
                    $request['payment_status'] = PaymentStatus::COMPLETED;
                } else if ($order_status == OrderEnum::CANCELLED && $order->payment_status == PaymentStatus::PENDING) {
                    $request['payment_status'] = PaymentStatus::CANCELLED;
                }
            }

            $order->update($request);
            DB::commit();

            $order = $order->fresh();
            $order->products;
            $order->sub_orders;
            $order->billing_address;
            $order->shipping_address;

            if (isset($request['order_status_id'])) {
                if ($order->order_status->name == OrderEnum::DELIVERED) {
                    $order->delivered_at = Carbon::now()->toDateString();
                    $order->save();
                }

                event(new UpdateOrderStatusEvent($order));
            }

            return $order;

        } catch (Exception $e) {

            DB::rollback();
            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function destroy($id)
    {
        try {

            return $this->model->where('id', $id)->where('consumer_id', Helpers::getCurrentUserId())->destroy($id);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function verifyPayment($request)
    {
        try {

            $order = $this->verifyOrderNumber($request->order_number);
            $transaction_id = $this->orderTransaction->where('order_id',$order->id)->pluck('transaction_id')->first();
            if (is_null($order) || !$transaction_id) {
                return $order;
            }

            switch ($order->payment_method) {
                case PaymentMethod::PAYPAL:
                    return PayPal::status($order, $transaction_id);

                case PaymentMethod::STRIPE:
                    return Stripe::status($order, $transaction_id);

                case PaymentMethod::RAZORPAY:
                    return RazorPay::status($order, $transaction_id);

                case PaymentMethod::MOLLIE:
                    return Mollie::status($order, $transaction_id);

                case PaymentMethod::PHONEPE:
                    return PhonePe::status($order, $transaction_id);

                case PaymentMethod::INSTAMOJO:
                    return InstaMojo::status($order, $transaction_id);

                default:
                    return $order;
            }

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function verifyOrderNumber($order_number)
    {
        try {

            $order = $this->model->with(config('enums.order.with'))->where('order_number', $order_number)->first();
            if (!$order) {
                throw new Exception('The provided order number is not valid.', 400);
            }

            $order->products;
            return $order;

        }  catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function rePayment($request)
    {
        try {

            $order = $this->verifyOrderNumber($request->order_number);
            if ($order->payment_status == PaymentStatus::COMPLETED) {
                throw new Exception('This payment has already been successfully processed previously.', 400);
            }

            return $this->createPayment($order, $request);

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function generateInvoiceUrl($order_number)
    {
        return route('invoice', ['order_number' => $order_number]);
    }

    public function getInvoiceUrl($order_number)
    {
        try {

            return $this->verifyOrderNumber($order_number)->invoice_url;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function getInvoice($request)
    {
        try {

            $order = $this->verifyOrderNumber($request->order_number);
            $invoice = [
                'orders' => $order,
                'settings' => Helpers::getSettings(),
            ];

            return PDF::loadView('emails.invoice', $invoice)->download('invoice-'.$order->order_number.'.pdf');

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function createPayment(Order $order, $request)
    {
        try {

            switch ($request->payment_method) {
                case PaymentMethod::PAYPAL:
                    return PayPal::getIntent($order, $request);

                case PaymentMethod::STRIPE:
                    return Stripe::getIntent($order, $request);

                case PaymentMethod::RAZORPAY:
                    return RazorPay::getIntent($order, $request);

                case PaymentMethod::MOLLIE:
                    return Mollie::getIntent($order, $request);

                case PaymentMethod::PHONEPE:
                    return PhonePe::getIntent($order, $request);

                case PaymentMethod::INSTAMOJO:
                    return InstaMojo::getIntent($order, $request);

                case PaymentMethod::CCAVENUE:
                    return CCAvenue::getIntent($order, $request);

                case PaymentMethod::COD:
                    return Cod::status($order, $request);

                default:
                    throw new Exception('The selected payment method is not valid for this transaction.', 400);
            }

            return $order;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }
}
