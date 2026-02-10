<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Http\Requests\CreateAttributeRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Repositories\Eloquents\AttributeRepository;

class AttributeController extends Controller
{
    public $repository;

    public function __construct(AttributeRepository $repository)
    {
        $this->authorizeResource(Attribute::class, 'attribute', [
            'except' => [ 'index', 'show' ],
        ]);
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/attribute",
     *      operationId="getAttributes",
     *      tags={"Attributes"},
     *      summary="Get list of product attributes",
     *      description="Returns all product attributes (e.g., Size, Color, Material) with their values. Used for product variations.",
     *      @OA\Parameter(
     *          name="paginate",
     *          in="query",
     *          description="Number of items per page",
     *          required=false,
     *          @OA\Schema(type="integer", example=20)
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *          description="Filter by status",
     *          required=false,
     *          @OA\Schema(type="integer", enum={0, 1})
     *      ),
     *      @OA\Parameter(
     *          name="store_slug",
     *          in="query",
     *          description="Filter attributes by store slug",
     *          required=false,
     *          @OA\Schema(type="string", example="tech-store")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="Size"),
     *                      @OA\Property(property="slug", type="string", example="size"),
     *                      @OA\Property(property="status", type="boolean", example=true),
     *                      @OA\Property(property="attribute_values", type="array",
     *                          @OA\Items(
     *                              @OA\Property(property="id", type="integer", example=1),
     *                              @OA\Property(property="value", type="string", example="Small"),
     *                              @OA\Property(property="slug", type="string", example="small")
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     */
    public function index(Request $request)
    {
        try {

            $attribute = $this->filter($this->repository->with(['attribute_values']), $request);
            return $attribute->latest('created_at')->paginate($request->paginate ?? $this->repository->count());

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
     *      path="/attribute",
     *      operationId="createAttribute",
     *      tags={"Attributes"},
     *      summary="Create a new attribute",
     *      description="Create a new product attribute with its values. Used to define product variations (e.g., Size with values Small, Medium, Large).",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name", "status"},
     *              @OA\Property(property="name", type="string", example="Size", description="Attribute name"),
     *              @OA\Property(property="status", type="integer", enum={0, 1}, example=1, description="Active status"),
     *              @OA\Property(property="attribute_values", type="array", description="List of attribute values",
     *                  @OA\Items(
     *                      @OA\Property(property="value", type="string", example="Small")
     *                  ),
     *                  example={{"value": "Small"}, {"value": "Medium"}, {"value": "Large"}}
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Attribute created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="name", type="string", example="Size"),
     *              @OA\Property(property="slug", type="string", example="size"),
     *              @OA\Property(property="attribute_values", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateAttributeRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/attribute/{id}",
     *      operationId="getAttributeById",
     *      tags={"Attributes"},
     *      summary="Get attribute by ID",
     *      description="Returns a single attribute with all its values.",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="slug", type="string"),
     *              @OA\Property(property="status", type="boolean"),
     *              @OA\Property(property="attribute_values", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *      @OA\Response(response=404, description="Attribute not found")
     * )
     */
    public function show(Attribute $attribute)
    {
        return $this->repository->show($attribute->id);
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
     *      path="/attribute/{id}",
     *      operationId="updateAttribute",
     *      tags={"Attributes"},
     *      summary="Update attribute",
     *      description="Update an existing attribute and its values.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Updated Size"),
     *              @OA\Property(property="status", type="integer", enum={0, 1}),
     *              @OA\Property(property="attribute_values", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", description="Existing value ID to update"),
     *                      @OA\Property(property="value", type="string")
     *                  )
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Attribute updated"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Attribute not found")
     * )
     */
    public function update(UpdateAttributeRequest $request, Attribute $attribute)
    {
        return $this->repository->update($request->all(), $attribute->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/attribute/{id}",
     *      operationId="deleteAttribute",
     *      tags={"Attributes"},
     *      summary="Delete attribute",
     *      description="Delete an attribute and all its values. Warning: This may affect products using this attribute.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Attribute deleted"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Attribute not found")
     * )
     */
    public function destroy(Request $request, Attribute $attribute)
    {
        return $this->repository->destroy($attribute->getId($request));
    }

    /**
     * @OA\Put(
     *      path="/attribute/{id}/{status}",
     *      operationId="updateAttributeStatus",
     *      tags={"Attributes"},
     *      summary="Update attribute status",
     *      description="Toggle attribute active/inactive status.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0, 1})),
     *      @OA\Response(response=200, description="Status updated"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    /**
     * @OA\Post(
     *      path="/attribute/deleteAll",
     *      operationId="deleteMultipleAttributes",
     *      tags={"Attributes"},
     *      summary="Delete multiple attributes",
     *      description="Bulk delete multiple attributes by their IDs.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"ids"},
     *              @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *          )
     *      ),
     *      @OA\Response(response=200, description="Attributes deleted"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function getAttributesExportUrl(Request $request)
    {
        return $this->repository->getAttributesExportUrl($request);
    }

    /**
     * @OA\Post(
     *      path="/attribute/csv/import",
     *      operationId="importAttributes",
     *      tags={"Attributes"},
     *      summary="Import attributes from CSV",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(mediaType="multipart/form-data",
     *              @OA\Schema(@OA\Property(property="file", type="string", format="binary"))
     *          )
     *      ),
     *      @OA\Response(response=200, description="Attributes imported")
     * )
     */
    public function import()
    {
        return $this->repository->import();
    }

    /**
     * @OA\Post(
     *      path="/attribute/csv/export",
     *      operationId="exportAttributes",
     *      tags={"Attributes"},
     *      summary="Export attributes to CSV",
     *      security={{"sanctum":{}}},
     *      @OA\Response(response=200, description="CSV file download", @OA\MediaType(mediaType="text/csv"))
     * )
     */
    public function export()
    {
        return $this->repository->export();
    }

    public function filter($attribute, $request)
    {
        if ($request->field && $request->sort) {
           $attribute = $attribute->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $attribute = $attribute->whereStatus($request->status);
        }

        if ($request->store_slug) {
            $store_slug = $request->store_slug;
            $attribute = $attribute->whereHas('products', function (Builder $products) use ($store_slug) {
                $products->whereHas('store', function (Builder $store) use ($store_slug) {
                    $store->where('slug', $store_slug);
                });
            });
        }

        return $attribute;
    }
}
