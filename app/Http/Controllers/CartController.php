<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncCartRequest;
use App\Http\Requests\CreateUpdateCartRequest;
use App\Repositories\Eloquents\CartRepository;

class CartController extends Controller
{
    public $repository;

    public function __construct(CartRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/cart",
     *      operationId="getCart",
     *      tags={"Cart"},
     *      summary="Get cart items",
     *      description="Get current user's cart items (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    /**
     * @OA\Post(
     *      path="/cart",
     *      operationId="addToCart",
     *      tags={"Cart"},
     *      summary="Add item to cart",
     *      description="Add a product to cart (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          required={"product_id","quantity"},
     *          @OA\Property(property="product_id", type="integer"),
     *          @OA\Property(property="quantity", type="integer"),
     *          @OA\Property(property="variation_id", type="integer")
     *      )),
     *      @OA\Response(response=201, description="Item added to cart"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateUpdateCartRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Put(
     *      path="/cart",
     *      operationId="updateCart",
     *      tags={"Cart"},
     *      summary="Update cart item",
     *      description="Update cart item quantity (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          @OA\Property(property="product_id", type="integer"),
     *          @OA\Property(property="quantity", type="integer")
     *      )),
     *      @OA\Response(response=200, description="Cart updated"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(CreateUpdateCartRequest $request)
    {
        return $this->repository->update($request->all());
    }

    /**
     * @OA\Delete(
     *      path="/cart/{id}",
     *      operationId="removeFromCart",
     *      tags={"Cart"},
     *      summary="Remove item from cart",
     *      description="Remove a product from cart (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Item removed from cart"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Cart item not found")
     * )
     */
    public function destroy(Request $request, Cart $cart)
    {
        return $this->repository->destroy($cart->getId($request));
    }

    /**
     * Replace the specified resource from storage.
     */
    public function replace(Request $request)
    {
        return $this->repository->replace($request);
    }

    /**
     * @OA\Post(
     *      path="/sync/cart",
     *      operationId="syncCart",
     *      tags={"Cart"},
     *      summary="Sync cart",
     *      description="Sync cart items (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          @OA\Property(property="items", type="array", @OA\Items(type="object"))
     *      )),
     *      @OA\Response(response=200, description="Cart synced"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function sync(SyncCartRequest $request)
    {
        return $this->repository->syncCart($request);
    }
}
