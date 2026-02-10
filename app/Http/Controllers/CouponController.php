<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Coupon;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateCouponRequest;
use App\Http\Requests\UpdateCouponRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\CouponRepository;

class CouponController extends Controller
{
    public $repository;

    public function __construct(CouponRepository $repository)
    {
        $this->authorizeResource(Coupon::class, 'coupon', [
            'except' => [ 'index', 'show' ],
        ]);

        return $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/coupon",
     *      operationId="getCoupons",
     *      tags={"Coupons"},
     *      summary="Get list of coupons",
     *      description="Returns a paginated list of discount coupons. Vendors only see their own coupons.",
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer", example=15)),
     *      @OA\Parameter(name="status", in="query", @OA\Schema(type="integer", enum={0, 1})),
     *      @OA\Parameter(name="field", in="query", @OA\Schema(type="string", example="created_at")),
     *      @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"asc", "desc"})),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="title", type="string", example="Summer Sale 20% Off"),
     *                      @OA\Property(property="code", type="string", example="SUMMER20"),
     *                      @OA\Property(property="type", type="string", enum={"fixed", "percentage"}, example="percentage"),
     *                      @OA\Property(property="amount", type="number", example=20),
     *                      @OA\Property(property="min_spend", type="number", example=50, description="Minimum cart total"),
     *                      @OA\Property(property="is_unlimited", type="boolean", example=false),
     *                      @OA\Property(property="usage_per_coupon", type="integer", example=100),
     *                      @OA\Property(property="usage_per_customer", type="integer", example=1),
     *                      @OA\Property(property="used", type="integer", example=45, description="Times used"),
     *                      @OA\Property(property="start_date", type="string", format="date"),
     *                      @OA\Property(property="end_date", type="string", format="date"),
     *                      @OA\Property(property="status", type="boolean"),
     *                      @OA\Property(property="is_expired", type="boolean"),
     *                      @OA\Property(property="is_apply_all", type="boolean", description="Apply to all products")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        try {

            $coupons = $this->filter($this->repository, $request);
            return $coupons->latest('created_at')->paginate($request->paginate ?? $coupons->count());

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
     *      path="/coupon",
     *      operationId="createCoupon",
     *      tags={"Coupons"},
     *      summary="Create a new coupon",
     *      description="Create a new discount coupon with usage limits and date restrictions.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"title", "code", "type", "amount", "status", "start_date", "end_date"},
     *              @OA\Property(property="title", type="string", example="Holiday Discount", description="Display title"),
     *              @OA\Property(property="code", type="string", example="HOLIDAY25", description="Unique coupon code"),
     *              @OA\Property(property="type", type="string", enum={"fixed", "percentage"}, example="percentage"),
     *              @OA\Property(property="amount", type="number", example=25, description="Discount amount/percentage"),
     *              @OA\Property(property="min_spend", type="number", example=100, description="Minimum order amount"),
     *              @OA\Property(property="is_unlimited", type="boolean", example=false),
     *              @OA\Property(property="usage_per_coupon", type="integer", example=500),
     *              @OA\Property(property="usage_per_customer", type="integer", example=1),
     *              @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *              @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *              @OA\Property(property="is_apply_all", type="boolean", default=true),
     *              @OA\Property(property="products", type="array", @OA\Items(type="integer"), description="Product IDs if not apply_all"),
     *              @OA\Property(property="exclude_products", type="array", @OA\Items(type="integer")),
     *              @OA\Property(property="status", type="integer", enum={0, 1})
     *          )
     *      ),
     *      @OA\Response(response=201, description="Coupon created"),
     *      @OA\Response(response=422, description="Validation error - Code already exists")
     * )
     */
    public function store(CreateCouponRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/coupon/{id}",
     *      operationId="getCouponById",
     *      tags={"Coupons"},
     *      summary="Get coupon by ID",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Coupon not found")
     * )
     */
    public function show(Coupon $coupon)
    {
        return $this->repository->show($coupon->id);
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
     *      path="/coupon/{id}",
     *      operationId="updateCoupon",
     *      tags={"Coupons"},
     *      summary="Update coupon",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(@OA\JsonContent(
     *          @OA\Property(property="title", type="string"),
     *          @OA\Property(property="code", type="string"),
     *          @OA\Property(property="amount", type="number"),
     *          @OA\Property(property="status", type="integer")
     *      )),
     *      @OA\Response(response=200, description="Coupon updated"),
     *      @OA\Response(response=404, description="Coupon not found")
     * )
     */
    public function update(UpdateCouponRequest $request, Coupon $coupon)
    {
        return $this->repository->update($request->all(), $coupon->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/coupon/{id}",
     *      operationId="deleteCoupon",
     *      tags={"Coupons"},
     *      summary="Delete coupon",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Coupon deleted"),
     *      @OA\Response(response=404, description="Coupon not found")
     * )
     */
    public function destroy(Request $request, Coupon $coupon)
    {
        return $this->repository->destroy($coupon->getId($request));
    }

    /**
     * @OA\Put(
     *      path="/coupon/{id}/{status}",
     *      operationId="updateCouponStatus",
     *      tags={"Coupons"},
     *      summary="Update coupon status",
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
     *      path="/coupon/deleteAll",
     *      operationId="deleteMultipleCoupons",
     *      tags={"Coupons"},
     *      summary="Delete multiple coupons",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(@OA\JsonContent(
     *          required={"ids"},
     *          @OA\Property(property="ids", type="array", @OA\Items(type="integer"))
     *      )),
     *      @OA\Response(response=200, description="Coupons deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($coupons, $request)
    {
        if (Helpers::isUserLogin()) {
            $roleName = Helpers::getCurrentRoleName();
            if ($roleName == RoleEnum::VENDOR) {
                $coupons = $coupons->where('created_by_id', Helpers::getCurrentUserId());
            }
        }

        if ($request->field && $request->sort) {
            $coupons = $coupons->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $coupons = $coupons->where('status',$request->status);
        }

        return $coupons;
    }
}
