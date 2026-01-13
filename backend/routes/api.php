<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Offer routes
    Route::get('/offers', [\App\Http\Controllers\Api\ServiceOfferController::class, 'index']);
    Route::get('/offers/categories', [\App\Http\Controllers\Api\ServiceOfferController::class, 'categories']);
    Route::get('/offers/{id}', [\App\Http\Controllers\Api\ServiceOfferController::class, 'show']);

    // Provider routes
    Route::prefix('provider')->group(function () {
        Route::post('/profile', [\App\Http\Controllers\Api\ProviderController::class, 'updateProfile']);
        Route::post('/photo', [\App\Http\Controllers\Api\ProviderController::class, 'uploadPhoto']);
        Route::post('/availability', [\App\Http\Controllers\Api\ProviderController::class, 'updateAvailability']);
        Route::post('/visibility', [\App\Http\Controllers\Api\ProviderController::class, 'toggleVisibility']);
    });
});
