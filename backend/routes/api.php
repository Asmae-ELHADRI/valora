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
    Route::get('/offers/my-offers', [\App\Http\Controllers\Api\ServiceOfferController::class, 'myOffers']);
    Route::get('/offers/{id}', [\App\Http\Controllers\Api\ServiceOfferController::class, 'show']);
    Route::post('/offers', [\App\Http\Controllers\Api\ServiceOfferController::class, 'store']);
    Route::put('/offers/{id}', [\App\Http\Controllers\Api\ServiceOfferController::class, 'update']);
    Route::delete('/offers/{id}', [\App\Http\Controllers\Api\ServiceOfferController::class, 'destroy']);

    // Application routes
    Route::post('/apply', [\App\Http\Controllers\Api\ServiceRequestController::class, 'apply']);
    Route::get('/my-applications', [\App\Http\Controllers\Api\ServiceRequestController::class, 'providerIndex']);
    Route::post('/service-requests/{id}/status', [\App\Http\Controllers\Api\ServiceRequestController::class, 'updateStatus']);

    // Messaging routes
    Route::get('/messages', [\App\Http\Controllers\Api\MessageController::class, 'index']);
    Route::get('/messages/unread-count', [\App\Http\Controllers\Api\MessageController::class, 'unreadCount']);
    Route::get('/messages/{userId}', [\App\Http\Controllers\Api\MessageController::class, 'show']);
    Route::post('/messages', [\App\Http\Controllers\Api\MessageController::class, 'store']);

    // Review routes
    Route::get('/my-reviews', [\App\Http\Controllers\Api\ReviewController::class, 'providerReviews']);
    Route::post('/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);

    // Security & Account
    Route::post('/password/update', [\App\Http\Controllers\Api\AuthController::class, 'updatePassword']);
    Route::delete('/account/delete', [\App\Http\Controllers\Api\AuthController::class, 'deleteAccount']);

    // Provider routes
    Route::prefix('provider')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ProviderController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ProviderController::class, 'show']);
        Route::post('/profile', [\App\Http\Controllers\Api\ProviderController::class, 'updateProfile']);
        Route::post('/photo', [\App\Http\Controllers\Api\ProviderController::class, 'uploadPhoto']);
        Route::post('/availability', [\App\Http\Controllers\Api\ProviderController::class, 'updateAvailability']);
        Route::post('/visibility', [\App\Http\Controllers\Api\ProviderController::class, 'toggleVisibility']);
    });

    // Client routes
    Route::prefix('client')->group(function () {
        Route::post('/profile', [\App\Http\Controllers\Api\ClientController::class, 'updateProfile']);
        Route::post('/photo', [\App\Http\Controllers\Api\ClientController::class, 'uploadPhoto']);
    });
});

// Auth (Public)
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Password Reset (Public)
Route::post('/password/forgot', [\App\Http\Controllers\Api\PasswordResetController::class, 'forgotSubmit']);
Route::post('/password/reset', [\App\Http\Controllers\Api\PasswordResetController::class, 'resetSubmit']);
