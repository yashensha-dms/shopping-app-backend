<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\Order;
use App\Helpers\Helpers;
use App\Enums\OrderEnum;
use App\Enums\PaymentStatus;
use App\Models\CommissionHistory;
use App\Http\Traits\CommissionTrait;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;

class CommissionHistoryRepository extends BaseRepository
{
    use CommissionTrait;

    protected $fieldSearchable = [
        'order.order_number' => 'like',
        'store.store_name' => 'like',
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
        return CommissionHistory::class;
    }

    public function show($id)
    {
        try {

            return $this->model->findOrFail($id);

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function store()
    {
        try {

            $settings = Helpers::getSettings();
            $refundableDays = $settings['refund']['refundable_days'];
            $refundableDate = now()->subDays($refundableDays)->toDateString();
            $orderStatusId = Helpers::getOrderStatusIdByName(OrderEnum::DELIVERED);
            $orders = Order::where('payment_status', PaymentStatus::COMPLETED)
                    ->where('order_status_id', $orderStatusId)
                    ->whereNotNull('delivered_at')
                    ->whereDate('delivered_at', '<=', $refundableDate)
                    ->get();

            if (!$orders) {
                throw new Exception('You can only compare similar products.', 400);
            }


            foreach($orders as $order) {
                $this->adminVendorCommission($order);
            }

        } catch (Exception $e){

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }
}
