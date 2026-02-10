<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWishlistRequest;
use App\Repositories\Eloquents\WishlistRepository;

class WishlistController extends Controller
{
    public $repository;

    public function __construct(WishlistRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/wishlist",
     *      operationId="getWishlist",
     *      tags={"Wishlist"},
     *      summary="Get wishlist items",
     *      description="Get current user's wishlist items (requires authentication)",
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
     *      path="/wishlist",
     *      operationId="addToWishlist",
     *      tags={"Wishlist"},
     *      summary="Add item to wishlist",
     *      description="Add a product to wishlist (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          required={"product_id"},
     *          @OA\Property(property="product_id", type="integer")
     *      )),
     *      @OA\Response(response=201, description="Item added to wishlist"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(CreateWishlistRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Delete(
     *      path="/wishlist/{id}",
     *      operationId="removeFromWishlist",
     *      tags={"Wishlist"},
     *      summary="Remove item from wishlist",
     *      description="Remove a product from wishlist (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Item removed from wishlist"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Wishlist item not found")
     * )
     */
    public function destroy(Request $request, $id)
    {
        return $this->repository->destroy($id ?? $request->id);
    }
}
