<?php

namespace App\Http\Controllers;

use Exception;
use App\Helpers\Helpers;
use App\Http\Traits\WalletPointsTrait;
use App\Http\Requests\WalletPointsRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\CreditDebitWalletRequest;
use App\Repositories\Eloquents\WalletRepository;

class WalletController extends Controller
{
    use WalletPointsTrait;

    public $repository;

    public function __construct(WalletRepository $repository)
    {
        return $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/wallet",
     *      operationId="getWalletTransactions",
     *      tags={"Wallet"},
     *      summary="Get wallet transactions",
     *      description="Returns user's wallet balance and transaction history with optional date filtering.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="consumer_id", in="query", description="Consumer ID (admin only)", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="search", in="query", description="Filter by transaction type", @OA\Schema(type="string")),
     *      @OA\Parameter(name="start_date", in="query", description="Filter start date", @OA\Schema(type="string", format="date")),
     *      @OA\Parameter(name="end_date", in="query", description="Filter end date", @OA\Schema(type="string", format="date")),
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="consumer_id", type="integer"),
     *              @OA\Property(property="balance", type="number", example=150.50, description="Current wallet balance"),
     *              @OA\Property(property="transactions", type="object",
     *                  @OA\Property(property="data", type="array",
     *                      @OA\Items(
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="type", type="string", enum={"credit", "debit"}, example="credit"),
     *                          @OA\Property(property="amount", type="number", example=50.00),
     *                          @OA\Property(property="detail", type="string", example="Wallet top-up"),
     *                          @OA\Property(property="created_at", type="string", format="date-time")
     *                      )
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(WalletPointsRequest $request)
    {
        try {

            return $this->filter($this->repository, $request);

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *      path="/wallet/credit",
     *      operationId="creditWallet",
     *      tags={"Wallet"},
     *      summary="Credit balance to wallet",
     *      description="Add funds to a consumer's wallet. Admin only.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"consumer_id", "balance"},
     *              @OA\Property(property="consumer_id", type="integer", example=5, description="Consumer to credit"),
     *              @OA\Property(property="balance", type="number", example=100.00, description="Amount to add")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Wallet credited"),
     *      @OA\Response(response=403, description="Forbidden - Admin only")
     * )
     */
    public function credit(CreditDebitWalletRequest $request)
    {
        return $this->repository->credit($request);
    }

    /**
     * @OA\Post(
     *      path="/wallet/debit",
     *      operationId="debitWallet",
     *      tags={"Wallet"},
     *      summary="Debit balance from wallet",
     *      description="Deduct funds from a consumer's wallet. Admin only.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"consumer_id", "balance"},
     *              @OA\Property(property="consumer_id", type="integer", example=5, description="Consumer to debit"),
     *              @OA\Property(property="balance", type="number", example=25.00, description="Amount to deduct")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Wallet debited"),
     *      @OA\Response(response=400, description="Insufficient balance"),
     *      @OA\Response(response=403, description="Forbidden - Admin only")
     * )
     */
    public function debit(CreditDebitWalletRequest $request)
    {
        return $this->repository->debit($request);
    }

    public function filter($wallet, $request)
    {
        $consumer_id = $request->consumer_id ?? Helpers::getCurrentUserId();
        $wallet = $wallet->where('consumer_id',$consumer_id)->first();

        if (!$wallet) {
            $wallet = $this->getWallet($request->consumer_id ?? Helpers::getCurrentUserId());
            $wallet = $wallet->fresh();
        }

        $transactions = $wallet->transactions()->where('type', 'LIKE', "%{$request->search}%");
        if ($request->start_date && $request->end_date) {
            $transactions->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $paginate = $request->paginate ?? $wallet->transactions()->count();
        $wallet->setRelation('transactions', $transactions->paginate($paginate));

        return $wallet;
    }
}
