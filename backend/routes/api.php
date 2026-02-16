<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Broadcast::routes();

    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/users/{id}/basic', [AuthController::class, 'getBasicInfo']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Offer routes (Protected)
    Route::get('/offers/my-offers', [\App\Http\Controllers\Api\ServiceOfferController::class, 'myOffers']);
    Route::post('/offers', [\App\Http\Controllers\Api\ServiceOfferController::class, 'store']);
    Route::put('/offers/{id}', [\App\Http\Controllers\Api\ServiceOfferController::class, 'update']);
    Route::delete('/offers/{id}', [\App\Http\Controllers\Api\ServiceOfferController::class, 'destroy']);

    // Application routes
    Route::post('/apply', [\App\Http\Controllers\Api\ServiceRequestController::class, 'apply']);
    Route::get('/my-applications', [\App\Http\Controllers\Api\ServiceRequestController::class, 'providerIndex']);
    Route::get('/my-requests', [\App\Http\Controllers\Api\ServiceRequestController::class, 'clientIndex']);
    Route::get('/requests/unread-count', [\App\Http\Controllers\Api\ServiceRequestController::class, 'unreadCounts']);
    Route::post('/invite', [\App\Http\Controllers\Api\ServiceRequestController::class, 'invite']);
    Route::post('/service-requests/{id}/status', [\App\Http\Controllers\Api\ServiceRequestController::class, 'updateStatus']);

    // Messaging routes (New System)
    Route::get('/messages/unread-count', [\App\Http\Controllers\Api\MessageController::class, 'unreadCount']);
    Route::get('/conversations', [\App\Http\Controllers\Api\ConversationController::class, 'index']);
    Route::post('/conversations', [\App\Http\Controllers\Api\ConversationController::class, 'store']); // Start/Get conversation
    Route::get('/conversations/{id}', [\App\Http\Controllers\Api\ConversationController::class, 'show']);
    Route::post('/conversations/{id}/messages', [\App\Http\Controllers\Api\ConversationController::class, 'sendMessage']);
    Route::post('/conversations/{id}/read', [\App\Http\Controllers\Api\ConversationController::class, 'markAsRead']);
    
    // Legacy Message Routes (Deprecating or adapting)
    // Route::get('/messages', [\App\Http\Controllers\Api\MessageController::class, 'index']);
    
    // Review routes
    Route::get('/reviews/provider', [\App\Http\Controllers\Api\ReviewController::class, 'providerReviews']);
    Route::get('/reviews/client', [\App\Http\Controllers\Api\ReviewController::class, 'clientReviews']);
    Route::post('/reviews', [\App\Http\Controllers\Api\ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [\App\Http\Controllers\Api\ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [\App\Http\Controllers\Api\ReviewController::class, 'destroy']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'destroy']);

    // Security & Account
    Route::post('/password/update', [\App\Http\Controllers\Api\AuthController::class, 'updatePassword']);
    Route::delete('/account/delete', [\App\Http\Controllers\Api\AuthController::class, 'deleteAccount']);


    // Provider profile management (restricted to providers)
    Route::middleware(['role:provider'])->prefix('provider')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Api\ProviderController::class, 'getProfile']);
        Route::post('/profile', [\App\Http\Controllers\Api\ProviderController::class, 'updateProfile']);
        Route::put('/profile', [\App\Http\Controllers\Api\ProviderController::class, 'updateProfile']);
        Route::post('/photo', [\App\Http\Controllers\Api\ProviderController::class, 'uploadPhoto']);
        Route::post('/cv', [\App\Http\Controllers\Api\ProviderController::class, 'uploadCv']);
        Route::post('/availability', [\App\Http\Controllers\Api\ProviderController::class, 'updateAvailability']);
        Route::post('/visibility', [\App\Http\Controllers\Api\ProviderController::class, 'toggleVisibility']);
        Route::get('/certificate', [\App\Http\Controllers\Api\CertificateController::class, 'show']);
    });

    // Provider browsing (accessible by all auth users)
    Route::prefix('provider')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ProviderController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\ProviderController::class, 'show']);
    });

    // Client routes
    Route::middleware(['role:client'])->prefix('client')->group(function () {
        Route::post('/profile', [\App\Http\Controllers\Api\ClientController::class, 'updateProfile']);
        Route::post('/photo', [\App\Http\Controllers\Api\ClientController::class, 'uploadPhoto']);
    });

    // Security routes
    Route::post('/report', [\App\Http\Controllers\Api\SecurityController::class, 'report']);
    Route::post('/block', [\App\Http\Controllers\Api\SecurityController::class, 'block']);
    Route::delete('/block/{id}', [\App\Http\Controllers\Api\SecurityController::class, 'unblock']);
    Route::get('/blocked-users', [\App\Http\Controllers\Api\SecurityController::class, 'blockedList']);

    // Admin Specific Routes
    Route::middleware(['admin'])->prefix('admin')->group(function () {
        // ... (Existing admin routes)
        Route::get('/stats', [\App\Http\Controllers\Api\AdminController::class, 'stats']);
        
        // Roles & Permissions (RBAC)
        Route::get('/roles', [\App\Http\Controllers\Api\AdminRoleController::class, 'index']);
        Route::post('/roles', [\App\Http\Controllers\Api\AdminRoleController::class, 'store']);
        Route::put('/roles/{id}', [\App\Http\Controllers\Api\AdminRoleController::class, 'update']);
        Route::delete('/roles/{id}', [\App\Http\Controllers\Api\AdminRoleController::class, 'destroy']);
        Route::get('/permissions', [\App\Http\Controllers\Api\AdminRoleController::class, 'getPermissions']);

        // ... (Other existing routes)
        Route::get('/conversations', [\App\Http\Controllers\Api\AdminController::class, 'conversations']);
        Route::get('/conversations/{user1}/{user2}', [\App\Http\Controllers\Api\AdminController::class, 'getMessagesBetweenUsers']);
        Route::get('/messages/attachments/{id}', [\App\Http\Controllers\Api\AdminController::class, 'downloadAttachment']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\Api\AdminController::class, 'store']);
        Route::put('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'update']);
        Route::post('/users/{id}/toggle-status', [\App\Http\Controllers\Api\AdminController::class, 'toggleStatus']);
        Route::delete('/users/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroy']);
        
        // Moderation & Complaints
        Route::get('/complaints', [\App\Http\Controllers\Admin\ComplaintController::class, 'index']);
        Route::get('/complaints/{id}', [\App\Http\Controllers\Admin\ComplaintController::class, 'show']);
        Route::post('/complaints/{id}/action', [\App\Http\Controllers\Admin\ComplaintController::class, 'action']);
        Route::post('/users/{id}/warn', [\App\Http\Controllers\Api\AdminController::class, 'warnUser']);
        Route::delete('/complaints/{id}/content', [\App\Http\Controllers\Api\AdminController::class, 'deleteReportedContent']);

        // Platform Governance
        Route::get('/settings', [\App\Http\Controllers\Api\AdminController::class, 'getSettings']);
        Route::put('/settings', [\App\Http\Controllers\Api\AdminController::class, 'updateSettings']);
        
        // Grades Management
        Route::get('/grades', [\App\Http\Controllers\Admin\GradeController::class, 'index']);
        Route::post('/grades', [\App\Http\Controllers\Admin\GradeController::class, 'store']);
        Route::post('/grades/assign', [\App\Http\Controllers\Admin\GradeController::class, 'assign']); // Manual assign
        Route::post('/grades/sync', [\App\Http\Controllers\Admin\GradeController::class, 'syncGrades']); // Auto sync
        Route::post('/grades/revoke', [\App\Http\Controllers\Admin\GradeController::class, 'revoke']); // Revoke
        Route::put('/grades/{id}', [\App\Http\Controllers\Admin\GradeController::class, 'update']);
        Route::delete('/grades/{id}', [\App\Http\Controllers\Admin\GradeController::class, 'destroy']);
        Route::get('/attributions', [\App\Http\Controllers\Admin\GradeController::class, 'attributions']);

        // Detailed Content Management
        Route::get('/offers-list', [\App\Http\Controllers\Api\AdminController::class, 'offersList']);
        Route::get('/missions-list', [\App\Http\Controllers\Api\AdminController::class, 'missionsList']);

        // Security Logs & Alerts
        Route::get('/logs', [\App\Http\Controllers\Api\AdminController::class, 'logs']);
        Route::get('/alerts', [\App\Http\Controllers\Api\AdminController::class, 'securityAlerts']);

        // Badge Management
        Route::get('/badges', [\App\Http\Controllers\Api\AdminController::class, 'badgesList']);
        Route::put('/badges/{id}', [\App\Http\Controllers\Api\AdminController::class, 'updateBadge']);

        // Moderation & Complaints (New)
        Route::get('/complaints', [\App\Http\Controllers\Admin\ComplaintController::class, 'index']);
        Route::get('/complaints/{id}', [\App\Http\Controllers\Admin\ComplaintController::class, 'show']);
        Route::post('/complaints/{id}/action', [\App\Http\Controllers\Admin\ComplaintController::class, 'action']);
        
        // Admin Conversations Overview
        Route::get('/conversations', [\App\Http\Controllers\Admin\ConversationController::class, 'index']);
        Route::get('/conversations/{sender}/{receiver}', [\App\Http\Controllers\Admin\ConversationController::class, 'show']);
    });
});

// Offers (Public)
Route::get('/offers', [\App\Http\Controllers\Api\ServiceOfferController::class, 'index']);
Route::get('/offers/categories', [\App\Http\Controllers\Api\ServiceOfferController::class, 'categories']);
Route::get('/offers/{id}', [\App\Http\Controllers\Api\ServiceOfferController::class, 'show']);

// Auth (Public)
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// Social Auth
Route::get('/auth/{provider}/redirect', [\App\Http\Controllers\Api\SocialAuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [\App\Http\Controllers\Api\SocialAuthController::class, 'handleProviderCallback']);

// Password Reset (Public)
Route::post('/password/forgot', [\App\Http\Controllers\Api\PasswordResetController::class, 'forgotSubmit']);
Route::post('/password/reset', [\App\Http\Controllers\Api\PasswordResetController::class, 'resetSubmit']);

// Provider (Public)
Route::prefix('provider')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\ProviderController::class, 'index']);
    Route::get('/{id}', [\App\Http\Controllers\Api\ProviderController::class, 'show']);
});

// Fallback login route for Sanctum/Broadcasting redirects
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
