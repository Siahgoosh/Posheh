<?php

use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminOfficeController;
use App\Http\Controllers\Api\Admin\AppReleaseAdminController;
use App\Http\Controllers\Api\Admin\BlogAdminController;
use App\Http\Controllers\Api\Admin\BroadcastAdminController;
use App\Http\Controllers\Api\Admin\SystemSettingsAdminController;
use App\Http\Controllers\Api\Admin\MarketingDashboardController;
use App\Http\Controllers\Api\Admin\DiscountCodeAdminController;
use App\Http\Controllers\Api\Admin\PaymentLeadAdminController;
use App\Http\Controllers\Api\Admin\TicketAdminController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\Blog\BlogController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\BotWebhookController;
use App\Http\Controllers\Api\ConsultantDirectoryController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\CrmController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\Office\OfficeController;
use App\Http\Controllers\Api\OfficePublicController;
use App\Http\Controllers\Api\OfficeSiteController;
use App\Http\Controllers\Api\Property\PropertyController;
use App\Http\Controllers\Api\Property\PropertyPublicController;
use App\Http\Controllers\Api\Owner\OwnerController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Visit\VisitController;
use App\Http\Controllers\Api\PublicApiController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\Subscription\SubscriptionController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Admin\PlanAdminController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\OwnerPortalController;
use App\Http\Controllers\Api\PropertyCompareController;
use App\Http\Controllers\Api\SavedSearchController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Middleware\EnsureOfficeIsActive;
use App\Http\Middleware\EnsureSubscriptionAccess;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/otp/send', [AuthController::class, 'sendOtp'])->middleware('throttle:10,1');
    Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:15,1');
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/blog', [BlogController::class, 'index']);
    Route::get('/blog/categories', [BlogController::class, 'categories']);
    Route::get('/blog/sitemap', [BlogController::class, 'sitemap']);
    Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'xml']);
    Route::get('/blog/{slug}', [BlogController::class, 'show']);
    Route::get('/downloads', [DownloadController::class, 'index']);
    Route::get('/downloads/file/{filename}', [DownloadController::class, 'file'])->where('filename', '.*');
    Route::get('/consultants', [ConsultantDirectoryController::class, 'index']);
    Route::get('/offices/{slug}', [OfficePublicController::class, 'show']);
    Route::get('/sites/{subdomain}', [OfficeSiteController::class, 'show']);
    Route::post('/sites/{subdomain}/visit-request', [OfficeSiteController::class, 'visitRequest'])->middleware('throttle:20,1');
    Route::post('/analytics/track', [AnalyticsController::class, 'track'])->middleware('throttle:120,1');
    Route::post('/auth/register', [RegistrationController::class, 'register'])->middleware('throttle:10,1');
    Route::get('/payments/zibal/callback', [SubscriptionController::class, 'zibalCallback'])->middleware('throttle:30,1');
    Route::get('/payments/aqayepardakht/callback', [SubscriptionController::class, 'aqayepardakhtCallback'])->middleware('throttle:30,1');
    Route::get('/owner-portal/{token}', [OwnerPortalController::class, 'show']);

    Route::post('/bots/telegram/{officeSlug}', [BotWebhookController::class, 'telegram']);
    Route::post('/bots/whatsapp/{officeSlug}', [BotWebhookController::class, 'whatsapp']);

    Route::get('/public/properties', [PublicApiController::class, 'properties'])->middleware('throttle:60,1');
    Route::get('/p/qr/{token}', [PropertyPublicController::class, 'byQr']);

    Route::middleware(['auth:sanctum', EnsureOfficeIsActive::class, EnsureSubscriptionAccess::class])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::get('/devices', [AuthController::class, 'devices']);
        });

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/filing/schema', [\App\Http\Controllers\Api\Property\FilingSchemaController::class, 'index']);
        Route::get('/filing/fields', [\App\Http\Controllers\Api\Property\FilingSchemaController::class, 'fields']);
        Route::get('/properties-export', [PropertyController::class, 'export']);
        Route::post('/properties-import', [PropertyController::class, 'import']);
        Route::apiResource('properties', PropertyController::class);
        Route::get('/properties/{id}/similar', [PropertyController::class, 'similar']);
        Route::get('/properties/{id}/share-message', [PropertyController::class, 'shareMessage']);
        Route::post('/properties/{id}/share', [PropertyController::class, 'share']);
        Route::post('/properties/{id}/favorite', [PropertyController::class, 'toggleFavorite']);
        Route::post('/properties/{id}/website-approval', [PropertyController::class, 'approveWebsite'])
            ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        Route::post('/properties/{id}/media', [PropertyController::class, 'uploadMedia']);
        Route::delete('/properties/{id}/media/{mediaId}', [PropertyController::class, 'deleteMedia']);
        Route::post('/properties/{id}/media/{mediaId}/cover', [PropertyController::class, 'setCoverMedia']);

        Route::apiResource('owners', OwnerController::class);
        Route::apiResource('customers', CustomerController::class);
        Route::get('/customers/{id}/matches', [CustomerController::class, 'matches']);
        Route::get('/visits', [VisitController::class, 'index']);
        Route::get('/visits/upcoming', [VisitController::class, 'upcoming']);
        Route::post('/visits', [VisitController::class, 'store']);
        Route::put('/visits/{id}', [VisitController::class, 'update']);
        Route::delete('/visits/{id}', [VisitController::class, 'destroy']);

        Route::get('/tickets', [TicketController::class, 'index']);
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']);

        Route::get('/saved-searches', [SavedSearchController::class, 'index'])->middleware('plan.feature:saved_searches');
        Route::post('/saved-searches', [SavedSearchController::class, 'store'])->middleware('plan.feature:saved_searches');
        Route::delete('/saved-searches/{id}', [SavedSearchController::class, 'destroy'])->middleware('plan.feature:saved_searches');
        Route::post('/saved-searches/{id}/run', [SavedSearchController::class, 'run'])->middleware('plan.feature:saved_searches');

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('plan.feature:activity_logs');

        Route::post('/properties/compare', [PropertyCompareController::class, 'compare'])->middleware('plan.feature:property_compare');

        Route::get('/wallet', [WalletController::class, 'balance']);
        Route::post('/wallet/top-up', [WalletController::class, 'topUp']);

        Route::get('/accounting', [AccountingController::class, 'index'])->middleware('plan.feature:accounting');
        Route::get('/accounting/summary', [AccountingController::class, 'summary'])->middleware('plan.feature:accounting');
        Route::post('/accounting', [AccountingController::class, 'store'])->middleware('plan.feature:accounting');

        Route::get('/crm/deals', [CrmController::class, 'index'])->middleware('plan.feature:crm');
        Route::get('/crm/pipeline', [CrmController::class, 'pipeline'])->middleware('plan.feature:crm');
        Route::get('/crm/follow-ups', [CrmController::class, 'followUps'])->middleware('plan.feature:crm');
        Route::post('/crm/deals', [CrmController::class, 'store'])->middleware('plan.feature:crm');
        Route::put('/crm/deals/{id}', [CrmController::class, 'update'])->middleware('plan.feature:crm');
        Route::get('/crm/deals/{id}/activities', [CrmController::class, 'activities'])->middleware('plan.feature:crm');
        Route::post('/crm/deals/{id}/activities', [CrmController::class, 'addActivity'])->middleware('plan.feature:crm');

        Route::get('/commissions', [CommissionController::class, 'index'])->middleware('plan.feature:commissions');
        Route::get('/commissions/settings', [CommissionController::class, 'settings'])->middleware('plan.feature:commissions');
        Route::put('/commissions/settings', [CommissionController::class, 'updateSettings'])->middleware('plan.feature:commissions');
        Route::post('/commissions', [CommissionController::class, 'store'])->middleware('plan.feature:commissions');
        Route::post('/commissions/{id}/pay', [CommissionController::class, 'markPaid'])->middleware('plan.feature:commissions');

        Route::get('/contracts/templates', [ContractController::class, 'templates']);
        Route::get('/contracts/fields', [ContractController::class, 'fields']);
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::post('/contracts', [ContractController::class, 'store']);
        Route::get('/contracts/{id}/download/{format}', [ContractController::class, 'download']);

        Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);

        Route::get('/api-keys', [ApiKeyController::class, 'index']);
        Route::post('/api-keys', [ApiKeyController::class, 'store']);
        Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy']);

        Route::prefix('office')->group(function () {
            Route::post('/', [OfficeController::class, 'store']);
            Route::get('/team', [OfficeController::class, 'team'])->middleware('plan.feature:team');
            Route::put('/settings', [OfficeController::class, 'updateSettings'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::post('/invite', [OfficeController::class, 'invite'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin')
                ->middleware('plan.feature:team');
            Route::get('/website', [OfficeController::class, 'websiteStatus'])->middleware('plan.feature:website_listing');
            Route::get('/website/visit-requests', [OfficeController::class, 'visitRequests'])->middleware('plan.feature:website_listing');
            Route::get('/website/pending-properties', [OfficeController::class, 'pendingWebsiteProperties'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin')
                ->middleware('plan.feature:website_listing');
            Route::post('/website/request', [OfficeController::class, 'requestWebsite'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin')
                ->middleware('plan.feature:website_listing');
            Route::post('/website/posts', [OfficeController::class, 'createSitePost'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin')
                ->middleware('plan.feature:website_listing');
        });

        Route::post('/owners/{id}/portal-link', [OwnerController::class, 'portalLink'])->middleware('plan.feature:owner_portal');

        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])
            ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        Route::post('/discount-codes/preview', [SubscriptionController::class, 'previewDiscount'])
            ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        Route::get('/subscription/current', [SubscriptionController::class, 'current']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

        Route::prefix('admin')->middleware(EnsureUserHasRole::class.':super_admin')->group(function () {
            Route::get('/marketing', [MarketingDashboardController::class, 'index']);
            Route::get('/system/sms', [MarketingDashboardController::class, 'smsStatus']);
            Route::get('/system-settings', [SystemSettingsAdminController::class, 'index']);
            Route::put('/system-settings', [SystemSettingsAdminController::class, 'update']);
            Route::get('/users/export', [AdminController::class, 'exportUsers']);
            Route::get('/payment-leads', [PaymentLeadAdminController::class, 'index']);
            Route::get('/payment-leads/export', [PaymentLeadAdminController::class, 'export']);
            Route::get('/discount-codes', [DiscountCodeAdminController::class, 'index']);
            Route::post('/discount-codes', [DiscountCodeAdminController::class, 'store']);
            Route::put('/discount-codes/{id}', [DiscountCodeAdminController::class, 'update']);
            Route::delete('/discount-codes/{id}', [DiscountCodeAdminController::class, 'destroy']);
            Route::get('/broadcasts', [BroadcastAdminController::class, 'index']);
            Route::post('/broadcasts', [BroadcastAdminController::class, 'store']);
            Route::get('/plans', [PlanAdminController::class, 'index']);
            Route::post('/plans', [PlanAdminController::class, 'store']);
            Route::put('/plans/{id}', [PlanAdminController::class, 'update']);
            Route::delete('/plans/{id}', [PlanAdminController::class, 'destroy']);
            Route::get('/offices', [AdminController::class, 'offices']);
            Route::put('/offices/{id}/plan-status', [AdminOfficeController::class, 'updatePlanStatus']);
            Route::put('/offices/{id}/website-status', [AdminOfficeController::class, 'updateWebsiteStatus']);
            Route::get('/analytics', [AdminController::class, 'analytics']);
            Route::get('/tickets', [TicketAdminController::class, 'index']);
            Route::post('/tickets/{id}/reply', [TicketAdminController::class, 'reply']);
            Route::put('/tickets/{id}/status', [TicketAdminController::class, 'updateStatus']);
            Route::get('/announcements', [AdminController::class, 'announcements']);
            Route::post('/announcements', [AdminController::class, 'createAnnouncement']);

            Route::get('/blog', [BlogAdminController::class, 'index']);
            Route::get('/blog/categories', [BlogAdminController::class, 'categories']);
            Route::post('/blog/analyze-seo', [BlogAdminController::class, 'analyzeSeo']);
            Route::post('/blog/upload-image', [BlogAdminController::class, 'uploadImage']);
            Route::post('/blog/upload-cover', [BlogAdminController::class, 'uploadCover']);
            Route::get('/blog/{id}', [BlogAdminController::class, 'show']);
            Route::post('/blog', [BlogAdminController::class, 'store']);
            Route::put('/blog/{id}', [BlogAdminController::class, 'update']);
            Route::delete('/blog/{id}', [BlogAdminController::class, 'destroy']);

            Route::get('/releases', [AppReleaseAdminController::class, 'index']);
            Route::post('/releases/upload', [AppReleaseAdminController::class, 'uploadFile']);
            Route::post('/releases', [AppReleaseAdminController::class, 'store']);
            Route::put('/releases/{id}', [AppReleaseAdminController::class, 'update']);
            Route::delete('/releases/{id}', [AppReleaseAdminController::class, 'destroy']);
        });
    });
});
