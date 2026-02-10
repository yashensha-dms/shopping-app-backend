<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Repositories\Eloquents\StoreRepository;

class StoreController extends Controller
{
    public $repository;

    public function __construct(StoreRepository $repository)
    {
        $this->authorizeResource(Store::class,'store',[
            'except' => [ 'index', 'show','store' ],
        ]);

        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/store",
     *      operationId="getStores",
     *      tags={"Stores"},
     *      summary="Get list of stores",
     *      description="Returns paginated list of vendor stores",
     *      @OA\Parameter(name="paginate", in="query", description="Number of items per page", @OA\Schema(type="integer")),
     *      @OA\Parameter(name="status", in="query", description="Filter by status", @OA\Schema(type="boolean")),
     *      @OA\Parameter(name="top_vendor", in="query", description="Get top vendors", @OA\Schema(type="boolean")),
     *      @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function index(Request $request)
    {
        $store = $this->filter($this->repository, $request);
        return $store->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
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
     *      path="/store",
     *      operationId="storeStore",
     *      tags={"Stores"},
     *      summary="Create a new store",
     *      description="Register a new vendor store",
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          required={"store_name","email"},
     *          @OA\Property(property="store_name", type="string", example="My Store"),
     *          @OA\Property(property="description", type="string"),
     *          @OA\Property(property="email", type="string", format="email"),
     *          @OA\Property(property="phone", type="string"),
     *          @OA\Property(property="country_id", type="integer"),
     *          @OA\Property(property="state_id", type="integer"),
     *          @OA\Property(property="city", type="string"),
     *          @OA\Property(property="address", type="string")
     *      )),
     *      @OA\Response(response=201, description="Store created successfully"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateStoreRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/store/{id}",
     *      operationId="getStoreById",
     *      tags={"Stores"},
     *      summary="Get store by ID",
     *      description="Returns a single store with details",
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Store not found")
     * )
     */
    public function show(Store $store)
    {
        return $this->repository->show($store->id);
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
     *      path="/store/{id}",
     *      operationId="updateStore",
     *      tags={"Stores"},
     *      summary="Update a store",
     *      description="Update store details (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\RequestBody(required=true, @OA\JsonContent(
     *          @OA\Property(property="store_name", type="string"),
     *          @OA\Property(property="description", type="string"),
     *          @OA\Property(property="email", type="string"),
     *          @OA\Property(property="phone", type="string")
     *      )),
     *      @OA\Response(response=200, description="Store updated successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Store not found")
     * )
     */
    public function update(UpdateStoreRequest $request, Store $store)
    {
        return $this->repository->update($request->all(), $store->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/store/{id}",
     *      operationId="deleteStore",
     *      tags={"Stores"},
     *      summary="Delete a store",
     *      description="Delete a store (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Response(response=200, description="Store deleted successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Store not found")
     * )
     */
    public function destroy(Request $request, Store $store)
    {
        return $this->repository->destroy($store->getId($request));
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

    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    public function approve($id, $status)
    {
        return $this->repository->approve($id, $status);
    }

    /**
     * @OA\Get(
     *      path="/store/slug/{slug}",
     *      operationId="getStoreBySlug",
     *      tags={"Stores"},
     *      summary="Get store by slug",
     *      description="Returns a single store by its slug",
     *      @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *      @OA\Response(response=200, description="Successful operation"),
     *      @OA\Response(response=404, description="Store not found")
     * )
     */
    public function getStoreBySlug($slug)
    {
        return $this->repository->getStoreBySlug($slug);
    }


    public function filter($store, $request)
    {
        isset($store->first()->vendor)?
            $store->first()->vendor->makeHidden(['store']) : $store;

        if ($request->field && $request->sort) {
            $store = $store->orderBy($request->field, $request->sort);
        }

        if ($request->top_vendor && $request->filter_by) {
            $store = Helpers::getTopVendors($store);
        }

        if (isset($request->status)) {
            $store = $store->where('status',$request->status);
        }

        return $store->with(config('enums.store.with'));
    }
}
