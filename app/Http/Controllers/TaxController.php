<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Tax;
use Illuminate\Http\Request;
use App\Http\Requests\CreateTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\TaxRepository;

class TaxController extends Controller
{
    protected $repository;

    public function __construct(TaxRepository $repository)
    {
        $this->authorizeResource(Tax::class, 'tax', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/tax",
     *      operationId="getTaxes",
     *      tags={"Taxes"},
     *      summary="Get list of tax rates",
     *      description="Returns all configured tax rates.",
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="status", in="query", @OA\Schema(type="integer", enum={0, 1})),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="Standard VAT"),
     *                      @OA\Property(property="rate", type="number", example=20.00, description="Tax percentage"),
     *                      @OA\Property(property="status", type="boolean"),
     *                      @OA\Property(property="created_at", type="string", format="date-time")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        try {

            $taxes = $this->filter($this->repository, $request);
            return $taxes->latest('created_at')->paginate($request->paginate ?? $taxes->count());

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
     *      path="/tax",
     *      operationId="createTax",
     *      tags={"Taxes"},
     *      summary="Create a new tax rate",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name", "rate", "status"},
     *              @OA\Property(property="name", type="string", example="GST 18%", description="Tax name"),
     *              @OA\Property(property="rate", type="number", example=18.00, description="Tax rate as percentage"),
     *              @OA\Property(property="status", type="integer", enum={0, 1}, example=1)
     *          )
     *      ),
     *      @OA\Response(response=201, description="Tax rate created"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateTaxRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/tax/{id}",
     *      operationId="getTaxById",
     *      tags={"Taxes"},
     *      summary="Get tax by ID",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Tax not found")
     * )
     */
    public function show(Tax $tax)
    {
        return $this->repository->show($tax->id);
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
     *      path="/tax/{id}",
     *      operationId="updateTax",
     *      tags={"Taxes"},
     *      summary="Update tax rate",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(@OA\JsonContent(
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="rate", type="number"),
     *          @OA\Property(property="status", type="integer")
     *      )),
     *      @OA\Response(response=200, description="Tax updated"),
     *      @OA\Response(response=404, description="Tax not found")
     * )
     */
    public function update(UpdateTaxRequest $request, Tax $tax)
    {
        return $this->repository->update($request->all(), $tax->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/tax/{id}",
     *      operationId="deleteTax",
     *      tags={"Taxes"},
     *      summary="Delete tax rate",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Tax deleted"),
     *      @OA\Response(response=404, description="Tax not found")
     * )
     */
    public function destroy(Request $request, Tax $tax)
    {
        return  $this->repository->destroy($tax->getId($request));
    }

    /**
     * @OA\Put(
     *      path="/tax/{id}/{status}",
     *      operationId="updateTaxStatus",
     *      tags={"Taxes"},
     *      summary="Update tax status",
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
     *      path="/tax/deleteAll",
     *      operationId="deleteMultipleTaxes",
     *      tags={"Taxes"},
     *      summary="Delete multiple tax rates",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(@OA\JsonContent(required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer")))),
     *      @OA\Response(response=200, description="Taxes deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($taxes, $request)
    {
        if ($request->field && $request->sort) {
            $taxes = $taxes->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $taxes = $taxes->where('status',$request->status);
        }

        return $taxes;
    }
}
