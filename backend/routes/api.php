<?php

use App\Http\Controllers\Api\Accounting\CommissionController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminExtendedController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Crm\CrmController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\Office\OfficeController;
use App\Http\Controllers\Api\Property\PropertyController;
use App\Http\Controllers\Api\Subscription\SubscriptionController;
use App\Http\Controllers\Api\ToolsController;
use App\Http\Controllers\Api\VirtualTour\PublicVirtualTourController;
use App\Http\Controllers\Api\VirtualTour\VirtualTourController;
use App\Http\Middleware\EnsureOfficeIsActive;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/otp/send', [AuthController::class, 'sendOtp']);
    Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp']);
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/payments/zarinpal/callback', [SubscriptionController::class, 'zarinpalCallback']);

    // Public virtual tour (like 360nama)
    Route::get('/tour/{slug}', [PublicVirtualTourController::class, 'show']);
    Route::post('/tour/{slug}/lead', [PublicVirtualTourController::class, 'submitLead']);

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

        // Virtual Tours
        Route::prefix('virtual-tours')->group(function () {
            Route::get('/', [VirtualTourController::class, 'index']);
            Route::post('/', [VirtualTourController::class, 'store']);
            Route::get('/{id}', [VirtualTourController::class, 'show']);
            Route::put('/{id}', [VirtualTourController::class, 'update']);
            Route::get('/{id}/analytics', [VirtualTourController::class, 'analytics']);
            Route::post('/{id}/scenes', [VirtualTourController::class, 'addScene']);
            Route::put('/{id}/scenes/{sceneId}', [VirtualTourController::class, 'updateScene']);
            Route::delete('/{id}/scenes/{sceneId}', [VirtualTourController::class, 'deleteScene']);
            Route::put('/{id}/scenes/{sceneId}/hotspots', [VirtualTourController::class, 'syncHotspots']);
            Route::post('/{id}/media', [VirtualTourController::class, 'uploadMedia']);
        });

        // CRM Pipeline
        Route::prefix('crm')->group(function () {
            Route::get('/pipeline', [CrmController::class, 'pipeline']);
            Route::post('/deals', [CrmController::class, 'storeDeal']);
            Route::put('/deals/{id}/move', [CrmController::class, 'moveDeal']);
            Route::put('/deals/{id}', [CrmController::class, 'updateDeal']);
        });

        // Accounting / Commissions
        Route::prefix('commissions')->group(function () {
            Route::get('/', [CommissionController::class, 'index']);
            Route::get('/summary', [CommissionController::class, 'summary']);
            Route::post('/', [CommissionController::class, 'store']);
        });

        // Tools
        Route::post('/tools/ad-copy', [ToolsController::class, 'generateAdCopy']);
        Route::get('/tools/compare', [ToolsController::class, 'compareProperties']);
        Route::get('/rentals', [ToolsController::class, 'rentalContracts']);
        Route::post('/rentals', [ToolsController::class, 'storeRentalContract']);
        Route::get('/rentals/expiring', [ToolsController::class, 'expiringRentals']);

        // Super Admin routes
        Route::prefix('admin')->middleware(EnsureUserHasRole::class.':super_admin')->group(function () {
            Route::get('/offices', [AdminController::class, 'offices']);
            Route::get('/analytics', [AdminController::class, 'analytics']);
            Route::get('/tickets', [AdminController::class, 'tickets']);
            Route::get('/announcements', [AdminController::class, 'announcements']);
            Route::post('/announcements', [AdminController::class, 'createAnnouncement']);
            Route::get('/health-scores', [AdminExtendedController::class, 'healthScores']);
            Route::post('/offices/{id}/toggle', [AdminExtendedController::class, 'toggleOffice']);
            Route::get('/mrr', [AdminExtendedController::class, 'mrr']);
            Route::get('/coupons', [AdminExtendedController::class, 'coupons']);
            Route::post('/coupons', [AdminExtendedController::class, 'createCoupon']);
            Route::get('/feature-flags', [AdminExtendedController::class, 'featureFlags']);
            Route::post('/feature-flags', [AdminExtendedController::class, 'setFeatureFlag']);
            Route::get('/audit-logs', [AdminExtendedController::class, 'auditLogs']);
            Route::get('/virtual-tour-stats', [AdminExtendedController::class, 'virtualTourStats']);
        });
    });
});
