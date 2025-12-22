<?php

namespace App\Repositories\Eloquents;

use Exception;
use App\Models\Point;
use App\Enums\WalletPointsDetail;
use App\Http\Traits\WalletPointsTrait;
use App\GraphQL\Exceptions\ExceptionHandler;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;

class PointsRepository extends BaseRepository
{
    use WalletPointsTrait;

    protected $fieldSearchable = [
        'transactions.type' =>'like',
        'transactions.detail' =>'like',
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
        return Point::class;
    }

    public function credit($request)
    {
        try {
            $points = $this->creditPoints($request->consumer_id, $request->balance, WalletPointsDetail::ADMIN_CREDIT);

            if ($points) {
                $points->setRelation('transactions', $points->transactions()
                    ->paginate($request->paginate ?? $points->transactions()->count()));
            }

            return $points;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    public function debit($request)
    {
        try {

            $points = $this->debitPoints($request->consumer_id, $request->balance, WalletPointsDetail::ADMIN_DEBIT);
            if ($points) {
                $points->setRelation('transactions', $points->transactions()
                    ->paginate($request->paginate ?? $points->transactions()->count()));
            }

            return $points;

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

}
