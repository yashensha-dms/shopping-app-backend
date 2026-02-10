<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use App\Enums\SortByEnum;
use App\Models\Attachment;
use Illuminate\Http\Request;
use App\Http\Requests\CreateAttachmentRequest;
use App\Repositories\Eloquents\AttachmentRepository;

class AttachmentController extends Controller
{
    public $repository;

    public function __construct(AttachmentRepository $repository)
    {
        $this->authorizeResource(Attachment::class, 'attachment', [
            'except' => [ 'index', 'show' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/attachment",
     *      operationId="getAttachments",
     *      tags={"Attachments"},
     *      summary="Get list of attachments",
     *      description="Returns a paginated list of all uploaded media files. Vendors and consumers only see their own uploads.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="paginate",
     *          in="query",
     *          description="Number of items per page",
     *          required=false,
     *          @OA\Schema(type="integer", example=20)
     *      ),
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Sort order",
     *          required=false,
     *          @OA\Schema(type="string", enum={"newest", "oldest", "smallest", "largest"})
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example=1),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="product-image.jpg"),
     *                      @OA\Property(property="file_name", type="string", example="1706789012_product-image.jpg"),
     *                      @OA\Property(property="mime_type", type="string", example="image/jpeg"),
     *                      @OA\Property(property="size", type="integer", example=102400, description="File size in bytes"),
     *                      @OA\Property(property="original_url", type="string", format="uri", example="https://domain.com/storage/attachments/1706789012_product-image.jpg"),
     *                      @OA\Property(property="created_at", type="string", format="date-time")
     *                  )
     *              ),
     *              @OA\Property(property="total", type="integer", example=150)
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $attachments = $this->filter($this->repository, $request);
        return $attachments->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
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
     *      path="/attachment",
     *      operationId="uploadAttachment",
     *      tags={"Attachments"},
     *      summary="Upload a new attachment",
     *      description="Upload an image or file. Supported formats: jpg, jpeg, png, gif, webp, svg. Accepts either a single file (file) or array of files (attachments).",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(property="file", type="string", format="binary", description="Single file to upload"),
     *                  @OA\Property(
     *                      property="attachments[]",
     *                      type="array",
     *                      @OA\Items(type="string", format="binary"),
     *                      description="Array of files to upload"
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="File uploaded successfully",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(
     *                  @OA\Property(property="id", type="integer", example=123),
     *                  @OA\Property(property="name", type="string", example="uploaded-image.jpg"),
     *                  @OA\Property(property="file_name", type="string", example="1706789012_uploaded-image.jpg"),
     *                  @OA\Property(property="mime_type", type="string", example="image/jpeg"),
     *                  @OA\Property(property="size", type="integer", example=102400),
     *                  @OA\Property(property="original_url", type="string", format="uri"),
     *                  @OA\Property(property="created_at", type="string", format="date-time")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="The file must be a file of type: jpg, jpeg, png, gif, webp.")
     *          )
     *      )
     * )
     */
    public function store(CreateAttachmentRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/attachment/{id}",
     *      operationId="getAttachmentById",
     *      tags={"Attachments"},
     *      summary="Get attachment by ID",
     *      description="Returns a single attachment with full details.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Attachment ID",
     *          required=true,
     *          @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="file_name", type="string"),
     *              @OA\Property(property="mime_type", type="string"),
     *              @OA\Property(property="size", type="integer"),
     *              @OA\Property(property="original_url", type="string", format="uri")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Attachment not found")
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
     *      path="/attachment/{id}",
     *      operationId="updateAttachment",
     *      tags={"Attachments"},
     *      summary="Update attachment metadata",
     *      description="Update attachment metadata such as alt text or title.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="updated-filename.jpg")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Attachment updated"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Attachment not found")
     * )
     */
    public function update(Request $request, Attachment $attachment)
    {
        return $this->repository->update($request->all(), $attachment->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/attachment/{id}",
     *      operationId="deleteAttachment",
     *      tags={"Attachments"},
     *      summary="Delete attachment",
     *      description="Permanently delete an attachment and its file from storage.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Attachment deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Attachment deleted successfully"),
     *              @OA\Property(property="success", type="boolean", example=true)
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Attachment not found")
     * )
     */
    public function destroy(Request $request, Attachment $attachment)
    {
        return $this->repository->destroy($attachment->getId($request));
    }

    /**
     * @OA\Post(
     *      path="/attachment/deleteAll",
     *      operationId="deleteMultipleAttachments",
     *      tags={"Attachments"},
     *      summary="Delete multiple attachments",
     *      description="Bulk delete multiple attachments by their IDs.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"ids"},
     *              @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={123, 124, 125}, description="Array of attachment IDs to delete")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Attachments deleted successfully"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function filter($attachments, $request)
    {
        $roleName = Helpers::getCurrentRoleName();
        if ($roleName == RoleEnum::VENDOR || $roleName == RoleEnum::CONSUMER) {
            $attachments = $this->repository->where('created_by_id', Helpers::getCurrentUserId());
        }

        if ($request->sort) {
            $attachments = $this->sort($attachments,$request->sort);
        }

        return $attachments;
    }

    public function sort($attachment, $sort)
    {
        switch ($sort) {
            case SortByEnum::NEWEST:
                return $attachment->latest('created_at');

            case SortByEnum::OLDEST:
                return $attachment->oldest('updated_at');

            case SortByEnum::SMALLEST:
                return $attachment->orderBy('size','asc');

            case SortByEnum::LARGEST:
                return $attachment->orderBy('size','desc');

            default:
                return $attachment;
        }
    }
}
