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

    /**
     * @OA\Get(
     *      path="/address",
     *      operationId="getAddresses",
     *      tags={"Addresses"},
     *      summary="Get user addresses",
     *      description="Returns all addresses for the authenticated user.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="paginate",
     *          in="query",
     *          description="Number of items per page",
     *          required=false,
     *          @OA\Schema(type="integer", example=10)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="current_page", type="integer", example=1),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="user_id", type="integer", example=5),
     *                      @OA\Property(property="title", type="string", example="Home"),
     *                      @OA\Property(property="street", type="string", example="123 Main Street"),
     *                      @OA\Property(property="city", type="string", example="New York"),
     *                      @OA\Property(property="pincode", type="string", example="10001"),
     *                      @OA\Property(property="phone", type="string", example="+1234567890"),
     *                      @OA\Property(property="is_default", type="boolean", example=true),
     *                      @OA\Property(property="country", type="object",
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string", example="United States")
     *                      ),
     *                      @OA\Property(property="state", type="object",
     *                          @OA\Property(property="id", type="integer"),
     *                          @OA\Property(property="name", type="string", example="New York")
     *                      )
     *                  )
     *              ),
     *              @OA\Property(property="total", type="integer", example=3)
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $address = $this->filter($this->repository);
        return $address->latest('created_at')->paginate($request->paginate ?? $address->count());
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
     *      path="/address",
     *      operationId="createAddress",
     *      tags={"Addresses"},
     *      summary="Create a new address",
     *      description="Add a new shipping/billing address for the authenticated user.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Address data",
     *          @OA\JsonContent(
     *              required={"title", "street", "city", "state_id", "country_id", "pincode", "phone"},
     *              @OA\Property(property="title", type="string", maxLength=255, example="Home", description="Address label (e.g., Home, Office, Warehouse)"),
     *              @OA\Property(property="street", type="string", example="123 Main Street, Apt 4B", description="Street address"),
     *              @OA\Property(property="city", type="string", example="New York", description="City name"),
     *              @OA\Property(property="state_id", type="integer", example=5, description="State ID from /state endpoint"),
     *              @OA\Property(property="country_id", type="integer", example=1, description="Country ID from /country endpoint"),
     *              @OA\Property(property="pincode", type="string", example="10001", description="ZIP/Postal code"),
     *              @OA\Property(property="phone", type="string", example="+1234567890", description="Contact phone number"),
     *              @OA\Property(property="is_default", type="boolean", example=true, description="Set as default address")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Address created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="title", type="string", example="Home"),
     *              @OA\Property(property="street", type="string"),
     *              @OA\Property(property="city", type="string"),
     *              @OA\Property(property="is_default", type="boolean"),
     *              @OA\Property(property="message", type="string", example="Address created successfully")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(CreateAddressRequest $request)
    {
        return $this->repository->store($request);
    }

    /**
     * @OA\Get(
     *      path="/address/{id}",
     *      operationId="getAddressById",
     *      tags={"Addresses"},
     *      summary="Get address by ID",
     *      description="Returns a single address with full details.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Address ID",
     *          required=true,
     *          @OA\Schema(type="integer", example=1)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="title", type="string"),
     *              @OA\Property(property="street", type="string"),
     *              @OA\Property(property="city", type="string"),
     *              @OA\Property(property="pincode", type="string"),
     *              @OA\Property(property="phone", type="string"),
     *              @OA\Property(property="is_default", type="boolean"),
     *              @OA\Property(property="country", type="object"),
     *              @OA\Property(property="state", type="object")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Address not found")
     * )
     */
    public function show(Address $address)
    {
        return $this->repository->show($address->id);
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
     *      path="/address/{id}",
     *      operationId="updateAddress",
     *      tags={"Addresses"},
     *      summary="Update address",
     *      description="Update an existing address. Only the address owner can update it.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Address ID",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="title", type="string", example="Home Office"),
     *              @OA\Property(property="street", type="string", example="456 Updated Street"),
     *              @OA\Property(property="city", type="string", example="Los Angeles"),
     *              @OA\Property(property="state_id", type="integer"),
     *              @OA\Property(property="country_id", type="integer"),
     *              @OA\Property(property="pincode", type="string"),
     *              @OA\Property(property="phone", type="string"),
     *              @OA\Property(property="is_default", type="boolean")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Address updated successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden - Not the address owner"),
     *      @OA\Response(response=404, description="Address not found")
     * )
     */
    public function update(UpdateAddressRequest $request, Address $address)
    {
        return $this->repository->update($request->all(), $address->getId($request));
    }

    /**
     * @OA\Delete(
     *      path="/address/{id}",
     *      operationId="deleteAddress",
     *      tags={"Addresses"},
     *      summary="Delete address",
     *      description="Delete an address. Cannot delete default address if it's the only one.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          description="Address ID",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Address deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Address deleted successfully"),
     *              @OA\Property(property="success", type="boolean", example=true)
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=404, description="Address not found")
     * )
     */
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
