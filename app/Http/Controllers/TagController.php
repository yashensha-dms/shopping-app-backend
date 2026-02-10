<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use App\Http\Requests\CreateTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Repositories\Eloquents\TagRepository;

class TagController extends Controller
{
    public $repository;

    public function __construct(TagRepository $repository)
    {
        $this->authorizeResource(Tag::class,'tag', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/tag",
     *      operationId="getTags",
     *      tags={"Tags"},
     *      summary="Get list of tags",
     *      description="Returns all tags used for products and blogs. Can be filtered by type.",
     *      @OA\Parameter(name="paginate", in="query", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="type", in="query", description="Filter by tag type", @OA\Schema(type="string", enum={"product", "blog", "post"})),
     *      @OA\Parameter(name="status", in="query", @OA\Schema(type="integer", enum={0, 1})),
     *      @OA\Parameter(name="ids", in="query", description="Filter by specific IDs (comma-separated)", @OA\Schema(type="string")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="Featured"),
     *                      @OA\Property(property="slug", type="string", example="featured"),
     *                      @OA\Property(property="type", type="string", example="product"),
     *                      @OA\Property(property="description", type="string"),
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
        $tags = $this->filter($this->repository, $request);
        return $tags->latest('created_at')->paginate($request->paginate ?? $tags->count());
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
     *      path="/tag",
     *      operationId="createTag",
     *      tags={"Tags"},
     *      summary="Create a new tag",
     *      description="Create a new tag for products or blogs.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name", "type", "status"},
     *              @OA\Property(property="name", type="string", example="Summer Collection", description="Tag name"),
     *              @OA\Property(property="type", type="string", enum={"product", "blog", "post"}, example="product"),
     *              @OA\Property(property="description", type="string", example="Products from summer 2024 collection"),
     *              @OA\Property(property="status", type="integer", enum={0, 1}, example=1)
     *          )
     *      ),
     *      @OA\Response(response=201, description="Tag created"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateTagRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/tag/{id}",
     *      operationId="getTagById",
     *      tags={"Tags"},
     *      summary="Get tag by ID",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Tag not found")
     * )
     */
    public function show(Tag $tag)
    {
        return $this->repository->show($tag->id);
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
     *      path="/tag/{id}",
     *      operationId="updateTag",
     *      tags={"Tags"},
     *      summary="Update tag",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(@OA\JsonContent(
     *          @OA\Property(property="name", type="string"),
     *          @OA\Property(property="type", type="string"),
     *          @OA\Property(property="status", type="integer")
     *      )),
     *      @OA\Response(response=200, description="Tag updated"),
     *      @OA\Response(response=404, description="Tag not found")
     * )
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        return $this->repository->update($request->all(), $tag->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/tag/{id}",
     *      operationId="deleteTag",
     *      tags={"Tags"},
     *      summary="Delete tag",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Tag deleted"),
     *      @OA\Response(response=404, description="Tag not found")
     * )
     */
    public function destroy(Request $request, Tag $tag)
    {
        return $this->repository->destroy($tag->getId($request));
    }

    /**
     * @OA\Put(
     *      path="/tag/{id}/{status}",
     *      operationId="updateTagStatus",
     *      tags={"Tags"},
     *      summary="Update tag status",
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
     *      path="/tag/deleteAll",
     *      operationId="deleteMultipleTags",
     *      tags={"Tags"},
     *      summary="Delete multiple tags",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(@OA\JsonContent(required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer")))),
     *      @OA\Response(response=200, description="Tags deleted")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function getTagsExportUrl(Request $request)
    {
        return $this->repository->getTagsExportUrl($request);
    }

    /**
     * @OA\Post(
     *      path="/tag/csv/import",
     *      operationId="importTags",
     *      tags={"Tags"},
     *      summary="Import tags from CSV",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(@OA\MediaType(mediaType="multipart/form-data", @OA\Schema(@OA\Property(property="file", type="string", format="binary")))),
     *      @OA\Response(response=200, description="Tags imported")
     * )
     */
    public function import()
    {
        return $this->repository->import();
    }

    /**
     * @OA\Post(
     *      path="/tag/csv/export",
     *      operationId="exportTags",
     *      tags={"Tags"},
     *      summary="Export tags to CSV",
     *      security={{"sanctum":{}}},
     *      @OA\Response(response=200, description="CSV file download")
     * )
     */
    public function export()
    {
        return $this->repository->export();
    }

    public function filter($tags, $request)
    {
        if ($request->ids) {
            $ids = explode(',',$request->ids);
            $tags = $tags->findWhereIn('id',$ids);
        }

        if ($request->type) {
            $tags = $this->repository->whereType($request->type);
        }

        if ($request->field && $request->sort) {
            $tags = $tags->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $tags = $tags->where('status',$request->status);
        }

        return $tags;
    }
}
