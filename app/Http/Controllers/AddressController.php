<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Address;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Repositories\Eloquents\AddressRepository;

class AddressController extends Controller
{
    public $repository;

    public function __construct(AddressRepository $repository)
    {
        $this->repository = $repository;
    }

    
    public function index(Request $request)
    {
        $address = $this->filter($this->repository);
        return $address->latest('created_at')->paginate($request->paginate ?? $address->count());
    }

    
    public function store(CreateAddressRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function show(Address $address)
    {
        return $this->repository->show($address->id);
    }

    
    public function update(UpdateAddressRequest $request, Address $address)
    {
        return $this->repository->update($request->all(), $address->getId($request));
    }

    
    public function destroy(Request $request, Address $address)
    {
        return $this->repository->destroy($address->getId($request));
    }

    public function filter($address)
    {
        $roleName = Helpers::getCurrentRoleName();
        if ($roleName != RoleEnum::ADMIN) {
            $address->where('user_id', Helpers::getCurrentUserId());
        }

        return $address;
    }
}
