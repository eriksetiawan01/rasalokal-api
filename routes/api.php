<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route Register, Login, Logout
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

// Public Routes
Route::apiResource('/categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('/menus', MenuController::class)->only(['index', 'show']);
Route::apiResource('orders', OrderController::class)->only(['store']);

Route::apiResource('payments', PaymentController::class);
Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'update', 'destroy']);

Route::apiResource('/users', UserController::class);

// Protected Routes
Route::middleware(['auth:api'])->group(function () {

    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('/categories', CategoryController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('/menus', MenuController::class)->only(['store', 'update', 'destroy']);
    });

});
