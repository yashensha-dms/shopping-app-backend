<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\CreateCurrencyRequest;
use App\Http\Requests\UpdateCurrencyRequest;
use App\Repositories\Eloquents\CurrencyRepository;

class CurrencyController extends Controller
{
    protected $repository;

    public function __construct(CurrencyRepository $repository)
    {
        $this->authorizeResource(Currency::class, 'currency', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/currency",
     *      operationId="getCurrencies",
     *      tags={"Currencies"},
     *      summary="Get list of currencies",
     *      description="Returns all available currencies for the store.",
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="field", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"asc", "desc"})),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="code", type="string", example="USD"),
     *                      @OA\Property(property="symbol", type="string", example="$"),
     *                      @OA\Property(property="no_of_decimal", type="integer", example=2),
     *                      @OA\Property(property="exchange_rate", type="number", example=1.00),
     *                      @OA\Property(property="status", type="boolean", example=true),
     *                      @OA\Property(property="created_by_id", type="integer")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        try {

            $currencies = $this->filter($this->repository, $request);
            return $currencies->paginate($request->paginate ?? $currencies->count());

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *      path="/currency",
     *      operationId="createCurrency",
     *      tags={"Currencies"},
     *      summary="Create a new currency",
     *      description="Add a new currency with exchange rate.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"code", "symbol", "exchange_rate", "status"},
     *              @OA\Property(property="code", type="string", maxLength=3, example="EUR", description="ISO 4217 currency code"),
     *              @OA\Property(property="symbol", type="string", example="€", description="Currency symbol"),
     *              @OA\Property(property="no_of_decimal", type="integer", example=2, description="Decimal places"),
     *              @OA\Property(property="exchange_rate", type="number", example=0.92, description="Exchange rate from base currency"),
     *              @OA\Property(property="status", type="integer", enum={0, 1})
     *          )
     *      ),
     *      @OA\Response(response=201, description="Currency created"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateCurrencyRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/currency/{id}",
     *      operationId="getCurrencyById",
     *      tags={"Currencies"},
     *      summary="Get currency by ID",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Currency not found")
     * )
     */
    public function show(Currency $currency)
    {
        return $this->repository->show($currency->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * @OA\Put(
     *      path="/currency/{id}",
     *      operationId="updateCurrency",
     *      tags={"Currencies"},
     *      summary="Update currency",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(@OA\JsonContent(
     *          @OA\Property(property="code", type="string"),
     *          @OA\Property(property="symbol", type="string"),
     *          @OA\Property(property="exchange_rate", type="number"),
     *          @OA\Property(property="status", type="integer")
     *      )),
     *      @OA\Response(response=200, description="Currency updated"),
     *      @OA\Response(response=404, description="Currency not found")
     * )
     */
    public function update(UpdateCurrencyRequest $request, Currency $currency)
    {
        return $this->repository->update($request->all(), $currency->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/currency/{id}",
     *      operationId="deleteCurrency",
     *      tags={"Currencies"},
     *      summary="Delete currency",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Currency deleted"),
     *      @OA\Response(response=404, description="Currency not found")
     * )
     */
    public function destroy(Request $request, Currency $currency)
    {
        return  $this->repository->destroy($currency->getId($request));
    }

    /**
     * @OA\Put(
     *      path="/currency/{id}/{status}",
     *      operationId="updateCurrencyStatus",
     *      tags={"Currencies"},
     *      summary="Update currency status",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0, 1})),
     *      @OA\Response(response=200, description="Status updated")
     * )
     */
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    /**
     * @OA\Post(
     *      path="/currency/deleteAll",
     *      operationId="deleteMultipleCurrencies",
     *      tags={"Currencies"},
     *      summary="Delete multiple currencies",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(@OA\JsonContent(
     *          required={"ids"},
     *          @OA\Property(property="ids", type="array", @OA\Items(type="integer"))
     *      )),
     *      @OA\Response(response=200, description="Currencies deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($currencies, $request)
    {
        if ($request->field && $request->sort) {
            $currencies = $currencies->orderBy($request->field, $request->sort);
        }

        return $currencies;
    }
}
