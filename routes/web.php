<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

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

// Route::get('/', function () {
//     return to_route('install.completed');
// });

// Clear Cache
// Route::any('/install/{any}', function () {
//     abort(404);
// })->where('any', '.*');

Route::get('/', function () {
    return response()->json(['status' => 'Fastkart API running']);
});





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

Route::get('order/invoice/{order_number}', 'App\Http\Controllers\OrderController@getInvoice')->name('invoice');
