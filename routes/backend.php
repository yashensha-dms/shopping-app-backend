<?php

use Illuminate\Support\Facades\Route;

Route::post('/login', 'App\Http\Controllers\AuthController@backendLogin');
Route::group(['middleware' => ['localization','auth:sanctum']], function () {
  Route::get('settings', 'App\Http\Controllers\SettingController@index')->middleware('can:setting.index');
});



