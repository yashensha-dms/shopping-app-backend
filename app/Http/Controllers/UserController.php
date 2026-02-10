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

    /**
     * @OA\Get(
     *      path="/user",
     *      operationId="getUsers",
     *      tags={"Users"},
     *      summary="Get list of users",
     *      description="Returns a paginated list of all users. Requires admin authentication.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="paginate",
     *          in="query",
     *          description="Number of items per page",
     *          required=false,
     *          @OA\Schema(type="integer", example=15)
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *          description="Filter by user status (0=inactive, 1=active)",
     *          required=false,
     *          @OA\Schema(type="integer", enum={0, 1})
     *      ),
     *      @OA\Parameter(
     *          name="role",
     *          in="query",
     *          description="Filter by role name",
     *          required=false,
     *          @OA\Schema(type="string", example="consumer")
     *      ),
     *      @OA\Parameter(
     *          name="field",
     *          in="query",
     *          description="Field to sort by",
     *          required=false,
     *          @OA\Schema(type="string", example="created_at")
     *      ),
     *      @OA\Parameter(
     *          name="sort",
     *          in="query",
     *          description="Sort direction",
     *          required=false,
     *          @OA\Schema(type="string", enum={"asc", "desc"})
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example=1),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="name", type="string", example="John Doe"),
     *                      @OA\Property(property="email", type="string", example="john@example.com"),
     *                      @OA\Property(property="phone", type="string", example="1234567890"),
     *                      @OA\Property(property="status", type="boolean", example=true),
     *                      @OA\Property(property="created_at", type="string", format="date-time"),
     *                      @OA\Property(property="role", type="object",
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string")
     *                      )
     *                  )
     *              ),
     *              @OA\Property(property="last_page", type="integer", example=10),
     *              @OA\Property(property="per_page", type="integer", example=15),
     *              @OA\Property(property="total", type="integer", example=150)
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden - Insufficient permissions")
     * )
     */
    public function index(Request $request)
    {
        try {

            $users = $this->filter($this->repository, $request);
            return $users->latest('created_at')->paginate($request->paginate ?? $users->count());

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
     *      path="/user",
     *      operationId="createUser",
     *      tags={"Users"},
     *      summary="Create a new user",
     *      description="Create a new user account. Requires admin authentication.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="User data",
     *          @OA\JsonContent(
     *              required={"name", "email", "password", "password_confirmation", "phone", "country_code"},
     *              @OA\Property(property="name", type="string", maxLength=255, example="John Doe", description="Full name of the user"),
     *              @OA\Property(property="email", type="string", format="email", maxLength=255, example="john@example.com", description="Unique email address"),
     *              @OA\Property(property="password", type="string", format="password", minLength=8, example="password123", description="Password (minimum 8 characters)"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="password123", description="Password confirmation"),
     *              @OA\Property(property="phone", type="string", minLength=9, example="1234567890", description="Phone number"),
     *              @OA\Property(property="country_code", type="string", example="+1", description="Country calling code"),
     *              @OA\Property(property="role_id", type="integer", example=3, description="Role ID to assign"),
     *              @OA\Property(property="status", type="integer", enum={0, 1}, example=1, description="User status"),
     *              @OA\Property(property="profile_image_id", type="integer", example=45, description="Profile image attachment ID")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="User created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", example="john@example.com"),
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="created_at", type="string", format="date-time")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="The email has already been taken.")
     *          )
     *      )
     * )
     */
    public function store(CreateUserRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/user/{id}",
     *      operationId="getUserById",
     *      tags={"Users"},
     *      summary="Get user by ID",
     *      description="Returns a single user with full details including addresses, wallet, and points.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="User ID",
     *          required=true,
     *          @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", example="john@example.com"),
     *              @OA\Property(property="phone", type="string", example="1234567890"),
     *              @OA\Property(property="country_code", type="string", example="+1"),
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="profile_image", type="object",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="original_url", type="string")
     *              ),
     *              @OA\Property(property="addresses", type="array", @OA\Items(type="object")),
     *              @OA\Property(property="wallet", type="object",
     *                  @OA\Property(property="balance", type="number", example=100.50)
     *              ),
     *              @OA\Property(property="point", type="object",
     *                  @OA\Property(property="balance", type="integer", example=500)
     *              ),
     *              @OA\Property(property="role", type="object",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="User not found")
     * )
     */
    public function show(User $user)
    {
        return $this->repository->show($user->id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

    }

    /**
     * @OA\Put(
     *      path="/user/{id}",
     *      operationId="updateUser",
     *      tags={"Users"},
     *      summary="Update user",
     *      description="Update an existing user's information.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="User ID",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="John Updated"),
     *              @OA\Property(property="email", type="string", format="email", example="john.updated@example.com"),
     *              @OA\Property(property="phone", type="string", example="9876543210"),
     *              @OA\Property(property="status", type="integer", enum={0, 1}),
     *              @OA\Property(property="role_id", type="integer"),
     *              @OA\Property(property="profile_image_id", type="integer")
     *          )
     *      ),
     *      @OA\Response(response=200, description="User updated successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="User not found"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        return $this->repository->update($request->all(), $user->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/user/{id}",
     *      operationId="deleteUser",
     *      tags={"Users"},
     *      summary="Delete user",
     *      description="Soft delete a user account.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="User ID",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="User deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="User deleted successfully"),
     *              @OA\Property(property="success", type="boolean", example=true)
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden"),
     *      @OA\Response(response=404, description="User not found")
     * )
     */
    public function destroy(Request $request, User $user)
    {
       return  $this->repository->destroy($user->getId($request));
    }

    /**
     * @OA\Put(
     *      path="/user/{id}/{status}",
     *      operationId="updateUserStatus",
     *      tags={"Users"},
     *      summary="Update user status",
     *      description="Toggle user active/inactive status.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *      @OA\Parameter(name="status", in="path", required=true, @OA\Schema(type="integer", enum={0, 1})),
     *      @OA\Response(response=200, description="Status updated successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="User not found")
     * )
     */
    public function status($id, $status)
    {
        return $this->repository->status($id, $status);
    }

    public function deleteAddress(Request $request, User $user)
    {
        return $this->repository->deleteAddress($user->getId($request));
    }

    /**
     * @OA\Post(
     *      path="/user/deleteAll",
     *      operationId="deleteMultipleUsers",
     *      tags={"Users"},
     *      summary="Delete multiple users",
     *      description="Bulk delete multiple users by their IDs.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"ids"},
     *              @OA\Property(property="ids", type="array", @OA\Items(type="integer"), example={1, 2, 3})
     *          )
     *      ),
     *      @OA\Response(response=200, description="Users deleted successfully"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function deleteAll(Request $request)
    {
        return $this->repository->deleteAll($request->ids);
    }

    /**
     * @OA\Post(
     *      path="/user/csv/import",
     *      operationId="importUsers",
     *      tags={"Users"},
     *      summary="Import users from CSV",
     *      description="Bulk import users from a CSV file.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(property="file", type="string", format="binary", description="CSV file")
     *              )
     *          )
     *      ),
     *      @OA\Response(response=200, description="Users imported successfully"),
     *      @OA\Response(response=422, description="Invalid file format")
     * )
     */
    public function import()
    {
        return $this->repository->import();
    }

    public function getUsersExportUrl(Request $request)
    {
        return $this->repository->getUsersExportUrl($request);
    }

    /**
     * @OA\Post(
     *      path="/user/csv/export",
     *      operationId="exportUsers",
     *      tags={"Users"},
     *      summary="Export users to CSV",
     *      description="Export all users data to a downloadable CSV file.",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="CSV file download",
     *          @OA\MediaType(mediaType="text/csv")
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
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
