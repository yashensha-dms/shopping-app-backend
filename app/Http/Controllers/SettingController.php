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

    
    public function frontSettings()
    {
        return $this->repository->frontSettings();
    }

    
    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        return $this->repository->update($request->all(), null);
    }
}
