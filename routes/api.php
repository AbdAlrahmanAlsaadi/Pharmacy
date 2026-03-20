<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(
    function () {

        Route::post('logout', [AuthController::class, 'logout']);
    Route::get('byName', [MedicineController::class, 'byName']);
        Route::get('show/{id}', [MedicineController::class, 'show']);
        Route::get('orders', [OrderController::class, 'index']);
    });


Route::post('register', [AuthController::class, 'register']);
Route::post('verify', [AuthController::class, 'verifyOtp']);
Route::post('login', [AuthController::class, 'login']);
Route::post('stores', [MedicineController::class, 'store']);

Route::middleware(['auth:sanctum', 'role:warehouse_owner'])->group(function () {
    Route::post('store', [MedicineController::class, 'store'])
        ->middleware('permission:add_medicines');

     Route::post('updateStatus/{o}', [OrderController::class, 'updateStatus']);


});
//Route::post('updateStatus/{o}', [OrderController::class, 'updateStatus']);


Route::post('download-report', [OrderController::class, 'downloadReport']);
Route::post('export', [OrderController::class, 'exportOrders']);

// للحصول على ملف PDF كرد API
Route::post('get', [OrderController::class, 'getReport']);

Route::get('ss', [MedicineController::class, 'byCategory']);

Route::middleware(['auth:sanctum', 'role:pharmacist'])->group(
    function () {
        Route::get('view', [MedicineController::class, 'byCategory'])
            ->middleware('permission:browse_medicines_by_category');
                Route::get('userfav', [MedicineController::class, 'index'])
                    ->middleware('permission:manage_favorites');
                Route::post('add', [MedicineController::class, 'add'])
                    ->middleware('permission:manage_favorites');
                Route::delete('/', [MedicineController::class, 'remove'])
                    ->middleware('permission:manage_favorites');


        Route::post('createorder', [OrderController::class, 'store'])
        ->middleware('permission:place_orders');
    }
);
Route::middleware('auth:sanctum')->post('/create-payment',[PaymentController::class,'create']);
Route::middleware('auth:sanctum')->post('createorder', [OrderController::class, 'store']);

Route::post('stripe/webhook',[PaymentController::class,'webhook']);

Route::get('/payment-status',[PaymentController::class,'checkStatus']);

Route::post('/payment-success',[PaymentController::class,'success']);

Route::post('/payment-cancel',[PaymentController::class,'cancel']);
Route::middleware('auth:sanctum')->post('/payment-topup', [PaymentController::class, 'topUp']);
Route::middleware('auth:sanctum')->post('test-balance', [PaymentController::class,'testBalance']);
