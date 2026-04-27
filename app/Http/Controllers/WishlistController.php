<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWishlistRequest;
use App\Repositories\Eloquents\WishlistRepository;

class WishlistController extends Controller
{
    public $repository;

    public function __construct(WishlistRepository $repository)
    {
        $this->repository = $repository;
    }

    
    public function index(Request $request)
    {
        return $this->repository->index($request);
    }

    
    public function store(CreateWishlistRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function destroy(Request $request, $id)
    {
        return $this->repository->destroy($id ?? $request->id);
    }
}
