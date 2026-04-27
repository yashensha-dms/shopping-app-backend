<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Enums\RoleEnum;
use App\Helpers\Helpers;
use Illuminate\Http\Request;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\GraphQL\Exceptions\ExceptionHandler;
use App\Repositories\Eloquents\UserRepository;

class UserController extends Controller
{
    protected $repository;

    public function __construct(UserRepository $repository)
    {
        $this->authorizeResource(User::class,'user');
        $this->repository = $repository;
    }

    
    public function index(Request $request)
    {
        try {

            $users = $this->filter($this->repository, $request);
            return $users->latest('created_at')->paginate($request->paginate ?? $users->count());

        } catch (Exception $e) {

            throw new ExceptionHandler($e->getMessage(), $e->getCode());
        }
    }

    
    public function store(CreateUserRequest $request)
    {
        return $this->repository->store($request);
    }

    
    public function show(User $user)
    {
        return $this->repository->show($user->id);
    }

    
    public function update(UpdateUserRequest $request, User $user)
    {
        return $this->repository->update($request->all(), $user->getId($request));
    }

    
    public function destroy(Request $request, User $user)
    {
       return  $this->repository->destroy($user->getId($request));
    }

    
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    public function deleteAddress(Request $request, User $user)
    {
        return $this->repository->deleteAddress($user->getId($request));
    }

    
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    
    public function import()
    {
        return $this->repository->import();
    }

    public function getUsersExportUrl(Request $request)
    {
        return $this->repository->getUsersExportUrl($request);
    }

    
    public function export()
    {
        return $this->repository->export();
    }

    public function filter($users, $request)
    {
        if (Helpers::isUserLogin()) {
            $roleName = Helpers::getCurrentRoleName();
            if ($roleName != RoleEnum::ADMIN) {
                $users = $users->where('created_by_id',Helpers::getCurrentUserId());
            }
        }

        if ($request->field && $request->sort) {
            $users = $users->orderBy($request->field, $request->sort);
        }

        if (isset($request->status)) {
            $users = $users->where('status',$request->status);
        }

        if ($request->isStoreExists) {
            $users = $users->whereIn('id', function ($query) {
                $query->select('vendor_id')->from('stores')->get();
            });

            if (!filter_var($request->isStoreExists, FILTER_VALIDATE_BOOLEAN)) {
                $users = $users->whereNotIn('id', function ($query) {
                    $query->select('vendor_id')->from('stores')->get();
                });
            }
        }

        if ($request->role) {
            $role = $request->role;
            $users = $users->whereHas("roles", function($query) use($role) {
                $query->whereName($role);
            });

        } else {

            $users = $users->whereHas("roles", function($query){
                $query->whereNotIn("name", [RoleEnum::ADMIN, RoleEnum::VENDOR]);
            });
        }

        return $users;
    }
}
