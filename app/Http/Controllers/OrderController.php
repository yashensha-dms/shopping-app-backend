<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Order;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\OrderRepository;

class OrderController extends Controller
{
    public $repository;

    public function __construct(OrderRepository $repository)
    {
        $this->repository = $repository;
        $this->authorizeResource(Order::class, 'order', [
            'except' => ['show'],
        ]);
    }

    /**
     * @OA\Get(
     *      path="/order",
     *      operationId="getOrders",
     *      tags={"Orders"},
     *      summary="Get list of orders",
     *      description="Returns paginated list of orders (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="paginate", in="query", description="Number of items per page", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="status", in="query", description="Filter by order status", @OA\Schema(type="string")),
     *      @OA\Parameter(name="start_date", in="query", description="Filter by start date", @OA\Schema(type="string", format="date")),
     *      @OA\Parameter(name="end_date", in="query", description="Filter by end date", @OA\Schema(type="string", format="date")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {

            $orders = $this->repository->whereNull('parent_id')->with('sub_orders');
            $orders = $this->filter($orders, $request);
            return $orders->latest('created_at')->paginate($request->paginate  ?? $orders->count());

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @OA\Post(
     *      path="/order",
     *      operationId="storeOrder",
     *      tags={"Orders"},
     *      summary="Place a new order",
     *      description="Create a new order/checkout (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          required={"products","billing_address_id","shipping_address_id","payment_method"},
     *          @OA\Property(property="products", type="array", @OA\Items(type="object")),
     *          @OA\Property(property="billing_address_id", type="integer"),
     *          @OA\Property(property="shipping_address_id", type="integer"),
     *          @OA\Property(property="payment_method", type="string"),
     *          @OA\Property(property="coupon", type="string"),
     *          @OA\Property(property="delivery_description", type="string")
     *      )),
     *      @OA\Response(response=201, description="Order placed successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateOrderRequest $request)
    {
        return $this->repository->placeOrder($request);
    }

    /**
     * @OA\Get(
     *      path="/order/{id}",
     *      operationId="getOrderById",
     *      tags={"Orders"},
     *      summary="Get order by ID",
     *      description="Returns a single order with details",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Order not found")
     * )
     */
    public function show($id)
    {
        return $this->repository->show($id);
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
     *      path="/order/{id}",
     *      operationId="updateOrder",
     *      tags={"Orders"},
     *      summary="Update an order",
     *      description="Update order status (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          @OA\Property(property="status", type="string"),
     *          @OA\Property(property="order_status_id", type="integer")
     *      )),
     *      @OA\Response(response=200, description="Order updated successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Order not found")
     * )
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        return $this->repository->update($request->all(), $order->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/order/{id}",
     *      operationId="deleteOrder",
     *      tags={"Orders"},
     *      summary="Delete an order",
     *      description="Delete an order (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Order deleted successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Order not found")
     * )
     */
    public function destroy(Request $request, Order $order)
    {
        return $this->repository->destroy($order->getId($request));
    }

    /**
     * Update Status the specified resource from storage.
     *
     * @param  int  $id
     * @param int $status
     * @return \Illuminate\Http\Response
     */
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    /**
     * @OA\Get(
     *      path="/trackOrder/{order_number}",
     *      operationId="trackOrder",
     *      tags={"Orders"},
     *      summary="Track an order",
     *      description="Track order status by order number",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="order_number", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Order not found")
     * )
     */
    public function trackOrder(Request $request)
    {
        return $this->repository->trackOrder($request->order_number);
    }

    /**
     * @OA\Get(
     *      path="/verifyPayment/{order_number}",
     *      operationId="verifyPayment",
     *      tags={"Orders"},
     *      summary="Verify payment",
     *      description="Verify payment status for an order",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="order_number", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Payment verified"),
     *      @OA\Response(response=400, description="Payment verification failed")
     * )
     */
    public function verifyPayment(Request $request)
    {
        return $this->repository->verifyPayment($request);
    }

    public function rePayment(Request $request)
    {
        return $this->repository->rePayment($request);
    }

    public function getInvoiceUrl(Request $request)
    {
        return $this->repository->getInvoiceUrl($request->order_number);
    }

    public function getInvoice(Request $request)
    {
        return $this->repository->getInvoice($request);
    }

    public function filter($orders, $request)
    {
        $roleName = Helpers::getCurrentRoleName();
        if ($roleName == RoleEnum::CONSUMER) {
            $orders = $orders->where('consumer_id',Helpers::getCurrentUserId());
        }

        if ($roleName == RoleEnum::VENDOR) {
            $orders = $this->repository->whereNotNull('parent_id')->where('store_id',Helpers::getCurrentVendorStoreId());
        }

        if ($request->field && $request->sort) {
            $orders = $orders->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $orders = $orders->where('status',$request->status);
        }

        if ($request->start_date && $request->end_date) {
            $orders = $orders->whereBetween('created_at',[$request->start_date, $request->end_date]);
        }

        return $orders;
    }
}
