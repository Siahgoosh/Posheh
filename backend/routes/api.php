<?php

use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminOfficeController;
use App\Http\Controllers\Api\Admin\AppReleaseAdminController;
use App\Http\Controllers\Api\Admin\BlogAdminController;
use App\Http\Controllers\Api\Admin\MarketingDashboardController;
use App\Http\Controllers\Api\Admin\PlanAdminController;
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

        Route::get('/accounting', [AccountingController::class, 'index']);
        Route::get('/accounting/summary', [AccountingController::class, 'summary']);
        Route::post('/accounting', [AccountingController::class, 'store']);

        Route::get('/crm/deals', [CrmController::class, 'index']);
        Route::get('/crm/pipeline', [CrmController::class, 'pipeline']);
        Route::get('/crm/follow-ups', [CrmController::class, 'followUps']);
        Route::post('/crm/deals', [CrmController::class, 'store']);
        Route::put('/crm/deals/{id}', [CrmController::class, 'update']);
        Route::get('/crm/deals/{id}/activities', [CrmController::class, 'activities']);
        Route::post('/crm/deals/{id}/activities', [CrmController::class, 'addActivity']);

        Route::get('/commissions', [CommissionController::class, 'index']);
        Route::get('/commissions/settings', [CommissionController::class, 'settings']);
        Route::put('/commissions/settings', [CommissionController::class, 'updateSettings']);
        Route::post('/commissions', [CommissionController::class, 'store']);
        Route::post('/commissions/{id}/pay', [CommissionController::class, 'markPaid']);

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
            Route::get('/team', [OfficeController::class, 'team']);
            Route::put('/settings', [OfficeController::class, 'updateSettings'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::post('/invite', [OfficeController::class, 'invite'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::get('/website', [OfficeController::class, 'websiteStatus']);
            Route::get('/website/visit-requests', [OfficeController::class, 'visitRequests']);
            Route::get('/website/pending-properties', [OfficeController::class, 'pendingWebsiteProperties'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::post('/website/request', [OfficeController::class, 'requestWebsite'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::post('/website/posts', [OfficeController::class, 'createSitePost'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        });

        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])
            ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        Route::get('/subscription/current', [SubscriptionController::class, 'current']);

        Route::prefix('admin')->middleware(EnsureUserHasRole::class.':super_admin')->group(function () {
            Route::get('/marketing', [MarketingDashboardController::class, 'index']);
            Route::get('/system/sms', [MarketingDashboardController::class, 'smsStatus']);
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
