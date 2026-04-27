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

    
    public function index(Request $request)
    {
        $reviews = $this->filter($this->repository->with(['product', 'store']), $request);
        return $reviews->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    
    public function frontIndex(Request $request)
    {
        $reviews = $this->filter($this->repository, $request);
        return $reviews->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    
    public function store(CreateReviewRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function update(UpdateReviewRequest $request, Review $review)
    {
        return $this->repository->update($request->all(), $review->getId($request));
    }

    
    public function destroy(Request $request, Review $review)
    {
        return $this->repository->destroy($review->getId($request));
    }

    
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
