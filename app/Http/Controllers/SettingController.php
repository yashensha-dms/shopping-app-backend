<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Http\Requests\UpdateSettingRequest;
use App\Repositories\Eloquents\SettingRepository;

class SettingController extends Controller
{
    public $repository;

    public function __construct(SettingRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return $this->repository->latest('created_at')->first();
    }

    /**
     * @OA\Get(
     *      path="/settings",
     *      operationId="getSettings",
     *      tags={"Settings"},
     *      summary="Get application settings",
     *      description="Returns public application settings",
     *      @OA\Response(response=200, description="Successful operation")
     * )
     */
    public function frontSettings()
    {
        return $this->repository->frontSettings();
    }

    /**
     * @OA\Put(
     *      path="/settings",
     *      operationId="updateSettings",
     *      tags={"Settings"},
     *      summary="Update application settings",
     *      description="Update application settings (requires authentication)",
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
     *      @OA\Response(response=200, description="Settings updated"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        return $this->repository->update($request->all(), null);
    }
}
