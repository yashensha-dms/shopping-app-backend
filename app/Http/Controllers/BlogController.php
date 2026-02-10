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

    /**
     * @OA\Get(
     *      path="/blog",
     *      operationId="getBlogs",
     *      tags={"Blogs"},
     *      summary="Get list of blog posts",
     *      description="Returns a paginated list of blog posts with optional category and tag filtering. Sticky posts are ordered first for public users.",
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer", example=10)),
     *      @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="integer", enum={0, 1})),
     *      @OA\Parameter(name="category", in="query", description="Filter by category slugs (comma-separated)", @OA\Schema(type="string", example="news,updates")),
     *      @OA\Parameter(name="tag", in="query", description="Filter by tag slugs (comma-separated)", @OA\Schema(type="string", example="featured,trending")),
     *      @OA\Parameter(name="ids", in="query", description="Filter by specific IDs (comma-separated)", @OA\Schema(type="string", example="1,2,3")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="title", type="string", example="Getting Started with E-commerce"),
     *                      @OA\Property(property="slug", type="string", example="getting-started-with-ecommerce"),
     *                      @OA\Property(property="description", type="string", example="A comprehensive guide..."),
     *                      @OA\Property(property="content", type="string"),
     *                      @OA\Property(property="meta_title", type="string"),
     *                      @OA\Property(property="meta_description", type="string"),
     *                      @OA\Property(property="is_featured", type="boolean"),
     *                      @OA\Property(property="is_sticky", type="boolean"),
     *                      @OA\Property(property="status", type="boolean"),
     *                      @OA\Property(property="blog_thumbnail", type="object"),
     *                      @OA\Property(property="categories", type="array", @OA\Items(type="object")),
     *                      @OA\Property(property="tags", type="array", @OA\Items(type="object")),
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

            $blog = $this->filter($this->repository, $request);
            return $blog->latest('created_at')->paginate($request->paginate ?? $blog->count());

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
     *      path="/blog",
     *      operationId="createBlog",
     *      tags={"Blogs"},
     *      summary="Create a new blog post",
     *      description="Create a new blog post with categories and tags.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"title", "content", "status"},
     *              @OA\Property(property="title", type="string", maxLength=255, example="New Blog Post", description="Blog post title"),
     *              @OA\Property(property="description", type="string", example="Short description for listing pages"),
     *              @OA\Property(property="content", type="string", example="Full blog content with HTML support"),
     *              @OA\Property(property="blog_thumbnail_id", type="integer", description="Thumbnail image attachment ID"),
     *              @OA\Property(property="blog_meta_image_id", type="integer", description="Meta/OG image attachment ID"),
     *              @OA\Property(property="meta_title", type="string", description="SEO meta title"),
     *              @OA\Property(property="meta_description", type="string", description="SEO meta description"),
     *              @OA\Property(property="is_featured", type="boolean", example=false),
     *              @OA\Property(property="is_sticky", type="boolean", example=false, description="Sticky posts appear first"),
     *              @OA\Property(property="status", type="integer", enum={0, 1}, example=1),
     *              @OA\Property(property="categories", type="array", @OA\Items(type="integer"), example={1, 2}),
     *              @OA\Property(property="tags", type="array", @OA\Items(type="integer"), example={3, 4})
     *          )
     *      ),
     *      @OA\Response(response=201, description="Blog post created successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateBlogRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/blog/{id}",
     *      operationId="getBlogById",
     *      tags={"Blogs"},
     *      summary="Get blog post by ID",
     *      description="Returns a single blog post with full content, categories, and tags.",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Blog not found")
     * )
     */
    public function show(Blog $blog)
    {
        return $this->repository->show($blog->id);
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
     *      path="/blog/{id}",
     *      operationId="updateBlog",
     *      tags={"Blogs"},
     *      summary="Update blog post",
     *      description="Update an existing blog post.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(@OA\JsonContent(
     *          @OA\Property(property="title", type="string"),
     *          @OA\Property(property="content", type="string"),
     *          @OA\Property(property="status", type="integer"),
     *          @OA\Property(property="categories", type="array", @OA\Items(type="integer")),
     *          @OA\Property(property="tags", type="array", @OA\Items(type="integer"))
     *      )),
     *      @OA\Response(response=200, description="Blog updated"),
     *      @OA\Response(response=404, description="Blog not found")
     * )
     */
    public function update(UpdateBlogRequest $request, Blog $blog)
    {
        return $this->repository->update($request->all(), $blog->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/blog/{id}",
     *      operationId="deleteBlog",
     *      tags={"Blogs"},
     *      summary="Delete blog post",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Blog deleted"),
     *      @OA\Response(response=404, description="Blog not found")
     * )
     */
    public function destroy(Request $request, Blog $blog)
    {
        return $this->repository->destroy($blog->getId($request));
    }

    /**
     * @OA\Put(
     *      path="/blog/{id}/{status}",
     *      operationId="updateBlogStatus",
     *      tags={"Blogs"},
     *      summary="Update blog status",
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
     *      path="/blog/deleteAll",
     *      operationId="deleteMultipleBlogs",
     *      tags={"Blogs"},
     *      summary="Delete multiple blogs",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(@OA\JsonContent(
     *          required={"ids"},
     *          @OA\Property(property="ids", type="array", @OA\Items(type="integer"))
     *      )),
     *      @OA\Response(response=200, description="Blogs deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    /**
     * @OA\Get(
     *      path="/blog/slug/{slug}",
     *      operationId="getBlogBySlug",
     *      tags={"Blogs"},
     *      summary="Get blog by slug",
     *      description="Returns a blog post by its URL-friendly slug.",
     *      @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string", example="getting-started-guide")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Blog not found")
     * )
     */
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
