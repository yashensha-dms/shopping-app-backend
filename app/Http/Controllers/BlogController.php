<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Blog;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Enums\SortByEnum;
use Illuminate\Http\Request;
use App\Http\Requests\CreateBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use Illuminate\Database\Eloquent\Builder;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\BlogRepository;

class BlogController extends Controller
{
    public $repository;

    public function __construct(BlogRepository $repository)
    {
        $this->authorizeResource(Blog::class, 'blog', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    
    public function index(Request $request)
    {
        try {

            $blog = $this->filter($this->repository, $request);
            return $blog->latest('created_at')->paginate($request->paginate ?? $blog->count());

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    
    public function store(CreateBlogRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function show(Blog $blog)
    {
        return $this->repository->show($blog->id);
    }

    
    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        return $this->repository->update($request->all(), $blog->getId($request));
    }

    
    public function destroy(Request $request, Blog $blog)
    {
        return $this->repository->destroy($blog->getId($request));
    }

    
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    
    public function getBlogsBySlug($slug)
    {
        return $this->repository->getBlogsBySlug($slug);
    }

    public function filter($blog, $request)
    {
        if (!Helpers::isUserLogin() || (Helpers::getCurrentRoleName() == RoleEnum::CONSUMER)) {
            $blog = $blog->orderBy('is_sticky', SortByEnum::DESC);
        }

        if ($request->ids) {
            $ids = explode(',',$request->ids);
            $blog->whereIn('id',$ids);
        }

        if ($request->field && $request->sort) {
            $blog =  $blog->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $blog = $blog->where('status',$request->status);
        }

        if ($request->category) {
            $slugs = explode(',', $request->category);
            $blog->whereHas('categories', function (Builder $query) use ($slugs) {
                $query->WhereIn('slug', $slugs);
            });
        }

        if ($request->tag) {
            $slugs = explode(',', $request->tag);
            $blog->whereHas('tags', function (Builder $query) use ($slugs) {
                $query->WhereIn('slug', $slugs);
            });
        }

        return $blog;
    }
}
