<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Repositories\Eloquents\ReviewRepository;

class ReviewController extends Controller
{
    public $repository;

    public function __construct(ReviewRepository $repository)
    {
        $this->authorizeResource(Review::class,'review',[
            'except' => 'edit', 'update', 'destroy'
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/review",
     *      operationId="getReviews",
     *      tags={"Reviews"},
     *      summary="Get list of reviews (Admin/Vendor)",
     *      description="Returns paginated reviews. Vendors only see reviews for their products.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="product_id", in="query", description="Filter by product ID", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="field", in="query", @OA\Schema(type="string")),
     *      @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", enum={"asc", "desc"})),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
     *                      @OA\Property(property="description", type="string", example="Excellent product, highly recommend!"),
     *                      @OA\Property(property="product_id", type="integer"),
     *                      @OA\Property(property="consumer_id", type="integer"),
     *                      @OA\Property(property="store_id", type="integer"),
     *                      @OA\Property(property="product", type="object"),
     *                      @OA\Property(property="store", type="object"),
     *                      @OA\Property(property="consumer", type="object"),
     *                      @OA\Property(property="created_at", type="string", format="date-time")
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        $reviews = $this->filter($this->repository->with(['product', 'store']), $request);
        return $reviews->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    /**
     * @OA\Get(
     *      path="/front/review",
     *      operationId="getFrontReviews",
     *      tags={"Reviews"},
     *      summary="Get public reviews",
     *      description="Returns public reviews for products. No authentication required.",
     *      @OA\Parameter(name="product_id", in="query", description="Filter by product ID", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function frontIndex(Request $request)
    {
        $reviews = $this->filter($this->repository, $request);
        return $reviews->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *      path="/review",
     *      operationId="createReview",
     *      tags={"Reviews"},
     *      summary="Create a new review",
     *      description="Submit a review for a product. User must have purchased the product.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"product_id", "rating", "description"},
     *              @OA\Property(property="product_id", type="integer", example=123, description="Product to review"),
     *              @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5, description="Rating from 1-5 stars"),
     *              @OA\Property(property="description", type="string", example="Great product! Fast shipping and excellent quality.", description="Review text"),
     *              @OA\Property(property="review_image_id", type="integer", description="Optional review image attachment ID")
     *          )
     *      ),
     *      @OA\Response(response=201, description="Review submitted"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error or not purchased")
     * )
     */
    public function store(CreateReviewRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(Review $review)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Review $review)
    {
        //
    }

    /**
     * @OA\Put(
     *      path="/review/{id}",
     *      operationId="updateReview",
     *      tags={"Reviews"},
     *      summary="Update review",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(@OA\JsonContent(
     *          @OA\Property(property="rating", type="integer", minimum=1, maximum=5),
     *          @OA\Property(property="description", type="string")
     *      )),
     *      @OA\Response(response=200, description="Review updated"),
     *      @OA\Response(response=404, description="Review not found")
     * )
     */
    public function update(UpdateReviewRequest $request, Review $review)
    {
        return $this->repository->update($request->all(), $review->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/review/{id}",
     *      operationId="deleteReview",
     *      tags={"Reviews"},
     *      summary="Delete review",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Review deleted"),
     *      @OA\Response(response=404, description="Review not found")
     * )
     */
    public function destroy(Request $request, Review $review)
    {
        return $this->repository->destroy($review->getId($request));
    }

    /**
     * @OA\Post(
     *      path="/review/deleteAll",
     *      operationId="deleteMultipleReviews",
     *      tags={"Reviews"},
     *      summary="Delete multiple reviews",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(@OA\JsonContent(
     *          required={"ids"},
     *          @OA\Property(property="ids", type="array", @OA\Items(type="integer"))
     *      )),
     *      @OA\Response(response=200, description="Reviews deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($reviews, $request)
    {
        if (Helpers::isUserLogin()) {
            $roleName = Helpers::getCurrentRoleName();
            if ($roleName == RoleEnum::VENDOR) {
                $reviews = $reviews->where('store_id',Helpers::getCurrentVendorStoreId());
            }
        }

        if ($request->product_id) {
            $reviews = $reviews->where('product_id',$request->product_id);
        }

        if ($request->field && $request->sort) {
            $reviews = $reviews->orderBy($request->field, $request->sort);
        }

        return $reviews;
    }
}
