<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AppReleaseAdminController;
use App\Http\Controllers\Api\Admin\BlogAdminController;
use App\Http\Controllers\Api\Blog\BlogController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\Office\OfficeController;
use App\Http\Controllers\Api\Property\PropertyController;
use App\Http\Controllers\Api\Subscription\SubscriptionController;
use App\Http\Middleware\EnsureOfficeIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/otp/send', [AuthController::class, 'sendOtp'])->middleware('throttle:10,1');
    Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:15,1');
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/blog', [BlogController::class, 'index']);
    Route::get('/blog/sitemap', [BlogController::class, 'sitemap']);
    Route::get('/blog/{slug}', [BlogController::class, 'show']);
    Route::get('/downloads', [DownloadController::class, 'index']);
    Route::get('/payments/zarinpal/callback', [SubscriptionController::class, 'zarinpalCallback'])
        ->middleware('throttle:30,1');

    // Authenticated routes
    Route::middleware(['auth:sanctum', EnsureOfficeIsActive::class])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::get('/devices', [AuthController::class, 'devices']);
        });

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::apiResource('properties', PropertyController::class);
        Route::get('/properties/{id}/similar', [PropertyController::class, 'similar']);
        Route::post('/properties/{id}/favorite', [PropertyController::class, 'toggleFavorite']);

        Route::prefix('office')->group(function () {
            Route::post('/', [OfficeController::class, 'store']);
            Route::get('/team', [OfficeController::class, 'team']);
            Route::post('/invite', [OfficeController::class, 'invite'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        });

        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])
            ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        Route::get('/subscription/current', [SubscriptionController::class, 'current']);

        // Super Admin routes
        Route::prefix('admin')->middleware(EnsureUserHasRole::class.':super_admin')->group(function () {
            Route::get('/offices', [AdminController::class, 'offices']);
            Route::get('/analytics', [AdminController::class, 'analytics']);
            Route::get('/tickets', [AdminController::class, 'tickets']);
            Route::get('/announcements', [AdminController::class, 'announcements']);
            Route::post('/announcements', [AdminController::class, 'createAnnouncement']);

            Route::get('/blog', [BlogAdminController::class, 'index']);
            Route::post('/blog/analyze-seo', [BlogAdminController::class, 'analyzeSeo']);
            Route::post('/blog/upload-image', [BlogAdminController::class, 'uploadImage']);
            Route::get('/blog/{id}', [BlogAdminController::class, 'show']);
            Route::post('/blog', [BlogAdminController::class, 'store']);
            Route::put('/blog/{id}', [BlogAdminController::class, 'update']);
            Route::delete('/blog/{id}', [BlogAdminController::class, 'destroy']);

            Route::get('/releases', [AppReleaseAdminController::class, 'index']);
            Route::post('/releases', [AppReleaseAdminController::class, 'store']);
            Route::put('/releases/{id}', [AppReleaseAdminController::class, 'update']);
            Route::delete('/releases/{id}', [AppReleaseAdminController::class, 'destroy']);
        });
    });
});
