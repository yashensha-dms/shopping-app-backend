<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateStoreProfileRequest;
use App\Repositories\Eloquents\AccountRepository;

class AccountController extends Controller
{
    protected $repository;

    public function __construct(AccountRepository $repository){
        $this->repository = $repository;
    }

    /**
     * @OA\Get(
     *      path="/self",
     *      operationId="getSelf",
     *      tags={"Account"},
     *      summary="Get current user profile",
     *      description="Returns the authenticated user's complete profile information including addresses, wallet, points, orders, and wishlist.",
     *      security={{"sanctum":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="name", type="string", example="John Doe"),
     *              @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *              @OA\Property(property="phone", type="string", example="1234567890"),
     *              @OA\Property(property="country_code", type="string", example="+1"),
     *              @OA\Property(property="status", type="boolean", example=true),
     *              @OA\Property(property="profile_image", type="object",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="original_url", type="string", format="uri")
     *              ),
     *              @OA\Property(property="addresses", type="array",
     *                  @OA\Items(
     *                      @OA\Property(property="id", type="integer"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="street", type="string"),
     *                      @OA\Property(property="city", type="string"),
     *                      @OA\Property(property="is_default", type="boolean")
     *                  )
     *              ),
     *              @OA\Property(property="wallet", type="object",
     *                  @OA\Property(property="balance", type="number", format="float", example=250.50)
     *              ),
     *              @OA\Property(property="point", type="object",
     *                  @OA\Property(property="balance", type="integer", example=1500)
     *              ),
     *              @OA\Property(property="orders_count", type="integer", example=12),
     *              @OA\Property(property="role", type="object",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="name", type="string", example="consumer")
     *              ),
     *              @OA\Property(property="permission", type="array", @OA\Items(type="string"))
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated - Invalid or expired token")
     * )
     */
    public function self()
    {
        return $this->repository->self();
    }

    /**
     * @OA\Put(
     *      path="/updatePassword",
     *      operationId="updateAccountPassword",
     *      tags={"Account"},
     *      summary="Update account password",
     *      description="Change the authenticated user's password. Requires current password verification.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Password change data",
     *          @OA\JsonContent(
     *              required={"current_password", "password", "password_confirmation"},
     *              @OA\Property(property="current_password", type="string", format="password", example="oldpassword123", description="Current password for verification"),
     *              @OA\Property(property="password", type="string", format="password", minLength=8, example="newpassword123", description="New password (minimum 8 characters)"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123", description="New password confirmation")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Password updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Password updated successfully"),
     *              @OA\Property(property="success", type="boolean", example=true)
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="The current password is incorrect.")
     *          )
     *      )
     * )
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        return $this->repository->updatePassword($request);
    }

    /**
     * @OA\Put(
     *      path="/updateProfile",
     *      operationId="updateProfile",
     *      tags={"Account"},
     *      summary="Update user profile",
     *      description="Update the authenticated user's profile information including name, email, phone, and profile image.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Profile data to update",
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", maxLength=255, example="John Updated", description="Full name"),
     *              @OA\Property(property="email", type="string", format="email", example="john.new@example.com", description="Email address (must be unique)"),
     *              @OA\Property(property="phone", type="string", example="9876543210", description="Phone number"),
     *              @OA\Property(property="country_code", type="string", example="+1", description="Country calling code"),
     *              @OA\Property(property="profile_image_id", type="integer", example=55, description="Profile image attachment ID")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Profile updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="id", type="integer"),
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string"),
     *              @OA\Property(property="message", type="string", example="Profile updated successfully")
     *          )
     *      ),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=422, description="Validation error")
     * )
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        return $this->repository->updateProfile($request);
    }

    /**
     * @OA\Put(
     *      path="/updateStoreProfile",
     *      operationId="updateStoreProfile",
     *      tags={"Account"},
     *      summary="Update vendor store profile",
     *      description="Update the vendor's store profile. Only available for users with vendor role.",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Store profile data",
     *          @OA\JsonContent(
     *              @OA\Property(property="store_name", type="string", example="My Updated Store"),
     *              @OA\Property(property="description", type="string", example="Welcome to my updated store"),
     *              @OA\Property(property="store_logo_id", type="integer", description="Store logo attachment ID"),
     *              @OA\Property(property="store_cover_id", type="integer", description="Store cover image attachment ID"),
     *              @OA\Property(property="facebook", type="string", format="uri", example="https://facebook.com/mystore"),
     *              @OA\Property(property="twitter", type="string", format="uri"),
     *              @OA\Property(property="instagram", type="string", format="uri"),
     *              @OA\Property(property="youtube", type="string", format="uri"),
     *              @OA\Property(property="pinterest", type="string", format="uri")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Store profile updated successfully"),
     *      @OA\Response(response=401, description="Unauthenticated"),
     *      @OA\Response(response=403, description="Forbidden - User is not a vendor")
     * )
     */
    public function updateStoreProfile(UpdateStoreProfileRequest $request)
    {
        return $this->repository->updateStoreProfile($request);
    }
}
