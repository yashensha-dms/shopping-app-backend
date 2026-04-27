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

    
    public function index(Request $request)
    {
        $store = $this->filter($this->repository, $request);
        return $store->latest('created_at')->paginate($request->paginate ?? $this->repository->count());
    }

    
    public function store(CreateStoreRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function show(Store $store)
    {
        return $this->repository->show($store->id);
    }

    
    public function update(UpdateStoreRequest $request, Store $store)
    {
        return $this->repository->update($request->all(), $store->getId($request));
    }

    
    public function destroy(Request $request, Store $store)
    {
        return $this->repository->destroy($store->getId($request));
    }

    
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
