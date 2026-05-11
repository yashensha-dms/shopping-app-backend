<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/session-proof', function () {
    session(['step_test' => 'OK']);
    return session('step_test');
});

//temp line above

Route::get('/', function () {
    return response()->json([
        'name' => 'Mstore API',
        'version' => '1.0.0',
        'status' => 'running',
        'graphql_endpoint' => url('/graphql'),
        'graphiql_endpoint' => url('/graphiql'),
    ]);
});

// Clear Cache
Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
    Artisan::call('clear-compiled');
    Artisan::call('storage:link');
});

Route::get('storage/{path}', function ($path) {
    $path = storage_path('app/public/' . $path);
    if (!File::exists($path)) {
        abort(404);
    }
    $file = File::get($path);
    $type = File::mimeType($path);
    return response($file, 200)->header("Content-Type", $type);
})->where('path', '.*');

Route::get('order/invoice/{order_number}', 'App\Http\Controllers\OrderController@getInvoice')->name('invoice');
