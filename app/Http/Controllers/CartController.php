<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\SyncCartRequest;
use App\Http\Requests\CreateUpdateCartRequest;
use App\Repositories\Eloquents\CartRepository;

class CartController extends Controller
{
    public $repository;

    public function __construct(CartRepository $repository)
    {
        $this->repository = $repository;
    }

    
    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    
    public function store(CreateUpdateCartRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function update(CreateUpdateCartRequest $request)
    {
        return $this->repository->update($request->all());
    }

    
    public function destroy(Request $request, Cart $cart)
    {
        return $this->repository->destroy($cart->getId($request));
    }

    
    public function sync(SyncCartRequest $request)
    {
        return $this->repository->syncCart($request);
    }
}
