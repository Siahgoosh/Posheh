<?php

use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\Accounting\AccountController as AccountingAccountController;
use App\Http\Controllers\Api\Accounting\CashAccountController as AccountingCashAccountController;
use App\Http\Controllers\Api\Accounting\ChequeController as AccountingChequeController;
use App\Http\Controllers\Api\Accounting\SettlementController as AccountingSettlementController;
use App\Http\Controllers\Api\Accounting\ReportController as AccountingReportController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\AdminAuditController;
use App\Http\Controllers\Api\Admin\AdminCommunicationController;
use App\Http\Controllers\Api\Admin\AdminDataController;
use App\Http\Controllers\Api\Admin\AdminPhase2Controller;
use App\Http\Controllers\Api\Admin\AdminCouponController;
use App\Http\Controllers\Api\Admin\AdminImpersonationController;
use App\Http\Controllers\Api\Admin\AdminPaymentController;
use App\Http\Controllers\Api\Admin\AdminSearchController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminDomainController;
use App\Http\Controllers\Api\Admin\AdminWalletController;
use App\Http\Middleware\EnsurePlatformStaff;
use App\Http\Controllers\Api\Admin\AdminOfficeController;
use App\Http\Controllers\Api\Admin\AdminOperationsController;
use App\Http\Controllers\Api\Admin\AppReleaseAdminController;
use App\Http\Controllers\Api\Admin\BlogCmsAdminController;
use App\Http\Controllers\Api\Admin\SeoGrowthAdminController;
use App\Http\Controllers\Api\Admin\TechnicalSeoAdminController;
use App\Http\Controllers\Api\Admin\CroAdminController;
use App\Http\Controllers\Api\Cro\CroPublicController;
use App\Http\Controllers\Api\Admin\MarketingDashboardController;
use App\Http\Controllers\Api\Admin\PlanAdminController;
use App\Http\Controllers\Api\Admin\TicketAdminController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\Auth\RegistrationController;
use App\Http\Controllers\Api\Blog\BlogController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\BotWebhookController;
use App\Http\Controllers\Api\ConsultantDirectoryController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\CommissionController;
use App\Http\Controllers\Api\Communication\CommunicationEmailWebhookController;
use App\Http\Controllers\Api\Communication\CommunicationPublicController;
use App\Http\Controllers\Api\Communication\CommunicationTelegramWebhookController;
use App\Http\Controllers\Api\CrmController;
use App\Http\Controllers\Api\CrmMetaController;
use App\Http\Controllers\Api\CrmSalesEngineController;
use App\Http\Controllers\Api\CrmIntelligenceController;
use App\Http\Controllers\Api\Dashboard\DashboardController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\Office\OfficeController;
use App\Http\Controllers\Api\Office\TeamChatController;
use App\Http\Controllers\Api\OfficePublicController;
use App\Http\Controllers\Api\OfficeSiteController;
use App\Http\Controllers\Api\Property\PropertyController;
use App\Http\Controllers\Api\Property\PropertyPublicController;
use App\Http\Controllers\Api\Owner\OwnerController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\Visit\VisitController;
use App\Http\Controllers\Api\VirtualTour\PublicVirtualTourController;
use App\Http\Controllers\Api\VirtualTour\VirtualTourController;
use App\Http\Controllers\Api\VirtualTour\VirtualTourAiController;
use App\Http\Controllers\Api\VirtualTour\VirtualTourEnterpriseController;
use App\Http\Controllers\Api\PublicApiController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\Subscription\SubscriptionController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Middleware\EnsureOfficeIsActive;
use App\Http\Middleware\EnsureSubscriptionAccess;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:15,1');
    Route::get('/auth/capabilities', [AuthController::class, 'capabilities']);
    Route::post('/auth/otp/send', [AuthController::class, 'sendOtp'])->middleware('throttle:10,1');
    Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp'])->middleware('throttle:15,1');
    Route::get('/plans', [SubscriptionController::class, 'plans']);
    Route::get('/blog', [BlogController::class, 'index']);
    Route::get('/blog/home', [BlogController::class, 'home']);
    Route::get('/blog/search', [BlogController::class, 'search']);
    Route::get('/blog/categories', [BlogController::class, 'categories']);
    Route::get('/blog/category/{slug}', [BlogController::class, 'categoryShow']);
    Route::get('/blog/tag/{slug}', [BlogController::class, 'tagShow']);
    Route::get('/blog/author/{slug}', [BlogController::class, 'authorShow']);
    Route::get('/blog/preview/{token}', [BlogController::class, 'preview']);
    Route::get('/blog/feed', [BlogController::class, 'feed']);
    Route::get('/blog/sitemap', [BlogController::class, 'sitemap']);
    Route::get('/sitemap.xml', [\App\Http\Controllers\SitemapController::class, 'xml']);
    Route::get('/blog/{slug}/related-suggestions', [BlogController::class, 'relatedSuggestions']);
    Route::get('/blog/{slug}', [BlogController::class, 'show']);
    Route::get('/downloads', [DownloadController::class, 'index']);
    Route::get('/downloads/file/{filename}', [DownloadController::class, 'file'])->where('filename', '.*');
    Route::get('/consultants', [ConsultantDirectoryController::class, 'index']);
    Route::get('/offices/{slug}', [OfficePublicController::class, 'show']);
    Route::get('/sites/{subdomain}', [OfficeSiteController::class, 'show']);
    Route::post('/sites/{subdomain}/visit-request', [OfficeSiteController::class, 'visitRequest'])->middleware('throttle:20,1');
    Route::post('/analytics/track', [AnalyticsController::class, 'track'])->middleware('throttle:120,1');
    Route::get('/cro/cta', [CroPublicController::class, 'resolveCta'])->middleware('throttle:60,1');
    Route::post('/cro/track', [CroPublicController::class, 'track'])->middleware('throttle:120,1');
    Route::post('/cro/leads', [CroPublicController::class, 'captureLead'])->middleware('throttle:20,1');
    Route::post('/auth/register', [RegistrationController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:10,1');
    Route::post('/auth/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');
    Route::get('/payments/zibal/callback', [SubscriptionController::class, 'zibalCallback'])->middleware('throttle:30,1');

    Route::post('/bots/telegram/{officeSlug}', [BotWebhookController::class, 'telegram']);
    Route::post('/bots/whatsapp/{officeSlug}', [BotWebhookController::class, 'whatsapp']);

    Route::prefix('communication')->middleware('throttle:180,1')->group(function () {
        Route::get('/config', [CommunicationPublicController::class, 'config']);
        Route::get('/health', [CommunicationPublicController::class, 'health']);
        Route::post('/visitors/init', [CommunicationPublicController::class, 'init']);
        Route::post('/visitors/heartbeat', [CommunicationPublicController::class, 'heartbeat']);
        Route::post('/visitors/events', [CommunicationPublicController::class, 'event']);
        Route::post('/leads', [CommunicationPublicController::class, 'captureLead'])->middleware('throttle:30,1');
        Route::get('/conversations/{uuid}/messages', [CommunicationPublicController::class, 'messages']);
        Route::post('/conversations/{uuid}/messages', [CommunicationPublicController::class, 'sendMessage']);
        Route::get('/telegram/webhook', [CommunicationTelegramWebhookController::class, 'ping']);
        Route::post('/telegram/webhook', [CommunicationTelegramWebhookController::class, 'handle']);
        Route::post('/email/inbound', [CommunicationEmailWebhookController::class, 'inbound']);
    });

    Route::get('/public/properties', [PublicApiController::class, 'properties'])->middleware('throttle:60,1');
    Route::get('/p/qr/{token}', [PropertyPublicController::class, 'byQr']);

    Route::get('/tour/{slug}', [PublicVirtualTourController::class, 'show'])->middleware('throttle:60,1');
    Route::get('/tour/{slug}/meta', [PublicVirtualTourController::class, 'meta']);
    Route::post('/tour/{slug}/events', [PublicVirtualTourController::class, 'recordEvents'])->middleware('throttle:120,1');
    Route::post('/tour/{slug}/verify-password', [PublicVirtualTourController::class, 'verifyPassword'])->middleware('throttle:20,1');
    Route::post('/tour/{slug}/lead', [PublicVirtualTourController::class, 'submitLead'])->middleware('throttle:20,1');
    Route::get('/tour-media', [\App\Http\Controllers\VirtualTourMediaController::class, 'show'])
        ->name('virtual-tour.media');

    Route::middleware(['auth:sanctum', EnsureOfficeIsActive::class, EnsureSubscriptionAccess::class])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::put('/password', [ProfileController::class, 'changePassword']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/logout-all', [AuthController::class, 'logoutAll']);
            Route::get('/devices', [AuthController::class, 'devices']);
        });

        Route::get('/wallet', [WalletController::class, 'show'])
            ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');

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
        Route::get('/customers/{id}/need-profile', [CustomerController::class, 'needProfile']);
        Route::put('/customers/{id}/need-profile', [CustomerController::class, 'upsertNeedProfile']);
        Route::get('/visits', [VisitController::class, 'index']);
        Route::get('/visits/upcoming', [VisitController::class, 'upcoming']);
        Route::post('/visits', [VisitController::class, 'store']);
        Route::put('/visits/{id}', [VisitController::class, 'update']);
        Route::post('/visits/{id}/complete', [VisitController::class, 'complete']);
        Route::delete('/visits/{id}', [VisitController::class, 'destroy']);

        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/tickets/{id}', [TicketController::class, 'show']);
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::post('/tickets/{id}/reply', [TicketController::class, 'reply']);

        Route::get('/accounting', [AccountingController::class, 'index']);
        Route::get('/accounting/summary', [AccountingController::class, 'summary']);
        Route::get('/accounting/dashboard', [AccountingController::class, 'dashboard']);
        Route::get('/accounting/reports/profit-loss', [AccountingController::class, 'profitAndLoss']);
        Route::post('/accounting', [AccountingController::class, 'store']);
        Route::post('/accounting/transfer', [AccountingController::class, 'transfer']);
        Route::post('/accounting/{id}/void', [AccountingController::class, 'void']);
        Route::post('/accounting/bootstrap', [AccountingController::class, 'bootstrap']);
        Route::get('/accounting/cheque-alerts', [AccountingController::class, 'chequeAlerts']);
        Route::post('/accounting/commissions/{commissionId}/settle', [AccountingController::class, 'settleCommission']);
        Route::get('/accounting/accounts', [AccountingAccountController::class, 'index']);
        Route::post('/accounting/accounts', [AccountingAccountController::class, 'store']);
        Route::put('/accounting/accounts/{id}', [AccountingAccountController::class, 'update']);
        Route::delete('/accounting/accounts/{id}', [AccountingAccountController::class, 'destroy']);
        Route::get('/accounting/cash-accounts', [AccountingCashAccountController::class, 'index']);
        Route::post('/accounting/cash-accounts', [AccountingCashAccountController::class, 'store']);
        Route::put('/accounting/cash-accounts/{id}', [AccountingCashAccountController::class, 'update']);
        Route::get('/accounting/pos-terminals', [AccountingCashAccountController::class, 'posIndex']);
        Route::post('/accounting/pos-terminals', [AccountingCashAccountController::class, 'posStore']);
        Route::get('/accounting/cheques/alerts', [AccountingChequeController::class, 'alerts']);
        Route::get('/accounting/cheques', [AccountingChequeController::class, 'index']);
        Route::post('/accounting/cheques', [AccountingChequeController::class, 'store']);
        Route::post('/accounting/cheques/{id}/status', [AccountingChequeController::class, 'updateStatus']);
        Route::get('/accounting/settlements', [AccountingSettlementController::class, 'index']);
        Route::get('/accounting/reports/debtors', [AccountingReportController::class, 'debtors']);
        Route::get('/accounting/reports/creditors', [AccountingReportController::class, 'creditors']);
        Route::get('/accounting/reports/consultants', [AccountingReportController::class, 'consultants']);
        Route::get('/accounting/reports/monthly-trend', [AccountingReportController::class, 'monthlyTrend']);
        Route::get('/accounting/people-ledger', [AccountingReportController::class, 'peopleLedger']);
        Route::get('/accounting/deals/{dealId}/finance', [AccountingReportController::class, 'dealFinance']);
        Route::get('/accounting/properties/{propertyId}/finance', [AccountingReportController::class, 'propertyFinance']);

        Route::get('/crm/deals', [CrmController::class, 'index']);
        Route::get('/crm/pipeline', [CrmController::class, 'pipeline']);
        Route::get('/crm/follow-ups', [CrmController::class, 'followUps']);
        Route::post('/crm/bootstrap', [CrmMetaController::class, 'bootstrap']);
        Route::get('/crm/stages', [CrmMetaController::class, 'stages']);
        Route::put('/crm/stages/{id}', [CrmMetaController::class, 'updateStage']);
        Route::get('/crm/sources', [CrmMetaController::class, 'sources']);
        Route::get('/crm/lost-reasons', [CrmMetaController::class, 'lostReasons']);
        Route::get('/crm/tags', [CrmMetaController::class, 'tags']);
        Route::post('/crm/tags', [CrmMetaController::class, 'storeTag']);
        Route::get('/crm/score-rules', [CrmMetaController::class, 'scoreRules']);
        Route::get('/crm/duplicates', [CrmMetaController::class, 'duplicateCheck']);
        Route::post('/crm/deals', [CrmController::class, 'store']);
        Route::get('/crm/deals/{id}', [CrmController::class, 'show']);
        Route::put('/crm/deals/{id}', [CrmController::class, 'update']);
        Route::delete('/crm/deals/{id}', [CrmController::class, 'destroy']);
        Route::get('/crm/deals/{id}/activities', [CrmController::class, 'activities']);
        Route::post('/crm/deals/{id}/activities', [CrmController::class, 'addActivity']);

        // CRM Phase 2 — Sales Engine
        Route::get('/crm/sales-queue', [CrmSalesEngineController::class, 'salesQueue']);
        Route::get('/crm/opportunities', [CrmSalesEngineController::class, 'opportunities']);
        Route::get('/crm/briefing', [CrmSalesEngineController::class, 'briefing']);
        Route::get('/crm/matching-weights', [CrmSalesEngineController::class, 'matchingWeights']);
        Route::put('/crm/matching-weights', [CrmSalesEngineController::class, 'updateMatchingWeights']);
        Route::get('/crm/properties/{propertyId}/reverse-matches', [CrmSalesEngineController::class, 'reverseMatch']);
        Route::get('/crm/negotiations', [CrmSalesEngineController::class, 'listNegotiations']);
        Route::post('/crm/negotiations', [CrmSalesEngineController::class, 'startNegotiation']);
        Route::get('/crm/negotiations/{id}', [CrmSalesEngineController::class, 'showNegotiation']);
        Route::get('/crm/offers', [CrmSalesEngineController::class, 'listOffers']);
        Route::post('/crm/offers', [CrmSalesEngineController::class, 'storeOffer']);
        Route::put('/crm/offers/{id}/status', [CrmSalesEngineController::class, 'updateOfferStatus']);
        Route::post('/crm/offers/{id}/convert-deal', [CrmSalesEngineController::class, 'convertOfferToDeal']);
        Route::post('/crm/presentations', [CrmSalesEngineController::class, 'presentProperty']);
        Route::post('/crm/feedback', [CrmSalesEngineController::class, 'storeFeedback']);
        Route::get('/crm/automation/rules', [CrmSalesEngineController::class, 'automationRules']);
        Route::post('/crm/automation/rules', [CrmSalesEngineController::class, 'storeAutomationRule']);
        Route::put('/crm/automation/rules/{id}', [CrmSalesEngineController::class, 'updateAutomationRule']);
        Route::get('/crm/automation/logs', [CrmSalesEngineController::class, 'automationLogs']);
        Route::get('/crm/deals/{dealId}/checklist', [CrmSalesEngineController::class, 'dealChecklist']);
        Route::post('/crm/deals/{dealId}/checklist/{itemId}/toggle', [CrmSalesEngineController::class, 'toggleChecklistItem']);
        Route::get('/crm/campaigns', [CrmSalesEngineController::class, 'campaigns']);
        Route::post('/crm/campaigns', [CrmSalesEngineController::class, 'storeCampaign']);

        // CRM Phase 3 — Intelligence / AI / Communication / Admin
        Route::get('/crm/executive', [CrmIntelligenceController::class, 'executive']);
        Route::get('/crm/agent-dashboard', [CrmIntelligenceController::class, 'agentDashboard']);
        Route::get('/crm/funnel-analytics', [CrmIntelligenceController::class, 'funnel']);
        Route::get('/crm/forecast', [CrmIntelligenceController::class, 'forecast']);
        Route::get('/crm/agents/performance', [CrmIntelligenceController::class, 'agents']);
        Route::get('/crm/sources/intelligence', [CrmIntelligenceController::class, 'sources']);
        Route::get('/crm/properties/{propertyId}/intelligence', [CrmIntelligenceController::class, 'propertyIntel']);
        Route::get('/crm/data-quality', [CrmIntelligenceController::class, 'dataQuality']);
        Route::get('/crm/pipeline-probabilities', [CrmIntelligenceController::class, 'probabilities']);
        Route::put('/crm/pipeline-probabilities', [CrmIntelligenceController::class, 'updateProbabilities']);
        Route::post('/crm/deals/bulk', [CrmIntelligenceController::class, 'bulkDealAction']);
        Route::get('/crm/ai/usage', [CrmIntelligenceController::class, 'aiUsage']);
        Route::get('/crm/ai/customers/{customerId}/summary', [CrmIntelligenceController::class, 'aiCustomerSummary']);
        Route::post('/crm/ai/message', [CrmIntelligenceController::class, 'aiMessage']);
        Route::get('/crm/ai/properties/{propertyId}/listing', [CrmIntelligenceController::class, 'aiListing']);
        Route::get('/crm/notifications', [CrmIntelligenceController::class, 'notifications']);
        Route::post('/crm/notifications/{id}/read', [CrmIntelligenceController::class, 'markNotificationRead']);
        Route::get('/crm/notification-preferences', [CrmIntelligenceController::class, 'notificationPreferences']);
        Route::put('/crm/notification-preferences', [CrmIntelligenceController::class, 'updateNotificationPreferences']);
        Route::get('/crm/message-templates', [CrmIntelligenceController::class, 'templates']);
        Route::post('/crm/message-templates', [CrmIntelligenceController::class, 'storeTemplate']);
        Route::post('/crm/message-templates/{id}/render', [CrmIntelligenceController::class, 'renderTemplate']);
        Route::get('/crm/integrations', [CrmIntelligenceController::class, 'integrations']);
        Route::get('/crm/custom-fields', [CrmIntelligenceController::class, 'customFields']);
        Route::post('/crm/custom-fields', [CrmIntelligenceController::class, 'storeCustomField']);
        Route::post('/crm/custom-field-values', [CrmIntelligenceController::class, 'setCustomFieldValue']);
        Route::get('/crm/saved-views', [CrmIntelligenceController::class, 'savedViews']);
        Route::post('/crm/saved-views', [CrmIntelligenceController::class, 'storeSavedView']);
        Route::get('/crm/onboarding', [CrmIntelligenceController::class, 'onboarding']);
        Route::post('/crm/onboarding/{id}/toggle', [CrmIntelligenceController::class, 'toggleOnboarding']);

        Route::get('/commissions', [CommissionController::class, 'index']);
        Route::get('/commissions/settings', [CommissionController::class, 'settings']);
        Route::put('/commissions/settings', [CommissionController::class, 'updateSettings']);
        Route::post('/commissions', [CommissionController::class, 'store']);
        Route::post('/commissions/{id}/pay', [CommissionController::class, 'markPaid']);

        Route::prefix('virtual-tours')->group(function () {
            Route::get('/dashboard', [VirtualTourEnterpriseController::class, 'dashboard']);
            Route::post('/import', [VirtualTourEnterpriseController::class, 'import']);
            Route::get('/', [VirtualTourEnterpriseController::class, 'list']);
            Route::post('/', [VirtualTourController::class, 'store']);
            Route::get('/{id}', [VirtualTourController::class, 'show']);
            Route::put('/{id}', [VirtualTourController::class, 'update']);
            Route::delete('/{id}', [VirtualTourEnterpriseController::class, 'destroy']);
            Route::post('/{id}/duplicate', [VirtualTourEnterpriseController::class, 'duplicate']);
            Route::post('/{id}/publish', [VirtualTourEnterpriseController::class, 'publish']);
            Route::post('/{id}/unpublish', [VirtualTourEnterpriseController::class, 'unpublish']);
            Route::post('/{id}/archive', [VirtualTourEnterpriseController::class, 'archive']);
            Route::post('/{id}/unarchive', [VirtualTourEnterpriseController::class, 'unarchive']);
            Route::put('/{id}/sharing', [VirtualTourEnterpriseController::class, 'updateSharing']);
            Route::get('/{id}/export/json', [VirtualTourEnterpriseController::class, 'exportJson']);
            Route::get('/{id}/export/zip', [VirtualTourEnterpriseController::class, 'exportZip']);
            Route::post('/{id}/backup', [VirtualTourEnterpriseController::class, 'backup']);
            Route::get('/{id}/versions', [VirtualTourEnterpriseController::class, 'versions']);
            Route::post('/{id}/versions/{versionId}/restore', [VirtualTourEnterpriseController::class, 'restoreVersion']);
            Route::get('/{id}/activity', [VirtualTourEnterpriseController::class, 'activity']);
            Route::get('/{id}/analytics', [VirtualTourController::class, 'analytics']);
            Route::get('/ai/capabilities', [VirtualTourAiController::class, 'capabilities']);
            Route::post('/{id}/ai/hotspot-suggestions', [VirtualTourAiController::class, 'hotspotSuggestions']);
            Route::post('/{id}/ai/scene-ordering', [VirtualTourAiController::class, 'sceneOrdering']);
            Route::post('/{id}/ai/generate-description', [VirtualTourAiController::class, 'generateDescription']);
            Route::post('/{id}/ai/generate-narration', [VirtualTourAiController::class, 'generateNarration']);
            Route::post('/{id}/scenes', [VirtualTourController::class, 'addScene']);
            Route::post('/{id}/scenes/upload', [VirtualTourController::class, 'uploadPanorama']);
            Route::post('/{id}/scenes/upload-image', [VirtualTourController::class, 'uploadSceneImage']);
            Route::put('/{id}/scenes/reorder', [VirtualTourController::class, 'reorderScenes']);
            Route::put('/{id}/scenes/{sceneId}', [VirtualTourController::class, 'updateScene']);
            Route::delete('/{id}/scenes/{sceneId}', [VirtualTourController::class, 'deleteScene']);
            Route::post('/{id}/scenes/{sceneId}/duplicate', [VirtualTourController::class, 'duplicateScene']);
            Route::post('/{id}/scenes/{sceneId}/publish', [VirtualTourController::class, 'publishScene']);
            Route::post('/{id}/scenes/{sceneId}/unpublish', [VirtualTourController::class, 'unpublishScene']);
            Route::post('/{id}/scenes/{sceneId}/default', [VirtualTourController::class, 'setDefaultScene']);
            Route::put('/{id}/scenes/{sceneId}/hotspots', [VirtualTourController::class, 'syncHotspots']);
            Route::post('/{id}/scenes/{sceneId}/hotspots', [VirtualTourController::class, 'addHotspot']);
            Route::put('/{id}/scenes/{sceneId}/hotspots/{hotspotId}', [VirtualTourController::class, 'updateHotspot']);
            Route::delete('/{id}/scenes/{sceneId}/hotspots/{hotspotId}', [VirtualTourController::class, 'deleteHotspot']);
            Route::post('/{id}/media', [VirtualTourController::class, 'uploadMedia']);
        });

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
            Route::get('/settings', [OfficeController::class, 'settings'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::put('/settings', [OfficeController::class, 'updateSettings'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::post('/settings/telegram/webhook', [OfficeController::class, 'reconnectTelegramWebhook'])
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
            Route::get('/domain', [OfficeController::class, 'domainStatus']);
            Route::post('/domain/check', [OfficeController::class, 'checkDomain'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::post('/domain/pay', [OfficeController::class, 'payDomain'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::post('/domain/connect', [OfficeController::class, 'connectDomain'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::post('/domain/verify', [OfficeController::class, 'verifyDomain'])
                ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
            Route::get('/team-chat', [TeamChatController::class, 'index']);
            Route::post('/team-chat', [TeamChatController::class, 'store']);
        });

        Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])
            ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        Route::post('/subscribe/bazaar/verify', [SubscriptionController::class, 'verifyBazaarPurchase'])
            ->middleware(EnsureUserHasRole::class.':office_manager,super_admin');
        Route::get('/subscription/current', [SubscriptionController::class, 'current']);

        Route::prefix('admin')->middleware(EnsurePlatformStaff::class)->group(function () {
            Route::get('/marketing', [MarketingDashboardController::class, 'index']);
            Route::get('/system/sms', [MarketingDashboardController::class, 'smsStatus']);
            Route::get('/search', AdminSearchController::class);

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::get('/users/platform-staff', [AdminUserController::class, 'platformStaff']);
            Route::post('/users/platform-staff', [AdminUserController::class, 'storePlatformStaff']);
            Route::get('/users/{id}', [AdminUserController::class, 'show']);
            Route::put('/users/{id}', [AdminUserController::class, 'update']);
            Route::post('/users/{id}/logout-all', [AdminUserController::class, 'logoutAll']);

            Route::get('/payments', [AdminPaymentController::class, 'index']);
            Route::get('/payments/{id}', [AdminPaymentController::class, 'show']);

            Route::get('/subscriptions', [AdminSubscriptionController::class, 'index']);
            Route::post('/subscriptions/{id}/extend', [AdminSubscriptionController::class, 'extend']);
            Route::post('/offices/{officeId}/assign-plan', [AdminSubscriptionController::class, 'assignPlan']);

            Route::get('/wallets', [AdminWalletController::class, 'index']);
            Route::get('/wallet-transactions', [AdminWalletController::class, 'transactions']);
            Route::post('/offices/{officeId}/wallet/adjust', [AdminWalletController::class, 'adjust']);

            Route::get('/domain-orders', [AdminDomainController::class, 'index']);
            Route::get('/domain-orders/dns-guide', [AdminDomainController::class, 'dnsGuide']);
            Route::post('/domain-orders/{id}/assign', [AdminDomainController::class, 'assign']);
            Route::put('/domain-orders/{id}', [AdminDomainController::class, 'update']);

            Route::get('/coupons', [AdminCouponController::class, 'index']);
            Route::post('/coupons', [AdminCouponController::class, 'store']);
            Route::put('/coupons/{id}', [AdminCouponController::class, 'update']);
            Route::delete('/coupons/{id}', [AdminCouponController::class, 'destroy']);

            Route::get('/audit-logs', [AdminAuditController::class, 'index']);
            Route::get('/settings', [AdminSettingsController::class, 'index']);
            Route::put('/settings', [AdminSettingsController::class, 'update']);

            Route::post('/impersonate/{userId}', [AdminImpersonationController::class, 'start']);
            Route::post('/impersonate/end', [AdminImpersonationController::class, 'end']);

            Route::get('/plans', [PlanAdminController::class, 'index']);
            Route::post('/plans', [PlanAdminController::class, 'store']);
            Route::put('/plans/{id}', [PlanAdminController::class, 'update']);
            Route::delete('/plans/{id}', [PlanAdminController::class, 'destroy']);
            Route::get('/offices', [AdminController::class, 'offices']);
            Route::get('/offices/{id}', [AdminOfficeController::class, 'show']);
            Route::put('/offices/{id}/status', [AdminOfficeController::class, 'updateStatus']);
            Route::put('/offices/{id}/plan-status', [AdminOfficeController::class, 'updatePlanStatus']);
            Route::put('/offices/{id}/website-status', [AdminOfficeController::class, 'updateWebsiteStatus']);
            Route::get('/analytics', [AdminController::class, 'analytics']);
            Route::get('/tickets', [TicketAdminController::class, 'index']);
            Route::get('/tickets/{id}', [TicketAdminController::class, 'show']);
            Route::post('/tickets/{id}/reply', [TicketAdminController::class, 'reply']);
            Route::put('/tickets/{id}/status', [TicketAdminController::class, 'updateStatus']);
            Route::put('/tickets/{id}/assign', [TicketAdminController::class, 'assign']);

            Route::prefix('communication')->group(function () {
                Route::get('/dashboard', [AdminCommunicationController::class, 'dashboard']);
                Route::post('/telegram/webhook/register', [AdminCommunicationController::class, 'registerTelegramWebhook']);
                Route::get('/inbox', [AdminCommunicationController::class, 'inbox']);
                Route::get('/conversations/{uuid}', [AdminCommunicationController::class, 'showConversation']);
                Route::post('/conversations/{uuid}/reply', [AdminCommunicationController::class, 'reply']);
                Route::put('/conversations/{uuid}', [AdminCommunicationController::class, 'updateConversation']);
                Route::post('/conversations/{uuid}/notes', [AdminCommunicationController::class, 'addNote']);
                Route::post('/conversations/{uuid}/tickets', [AdminCommunicationController::class, 'createTicket']);
                Route::post('/conversations/{uuid}/tickets/close', [AdminCommunicationController::class, 'closeTicket']);
                Route::get('/conversations/{uuid}/ai/suggestions', [AdminCommunicationController::class, 'aiSuggestions']);
                Route::get('/conversations/{uuid}/ai/summary', [AdminCommunicationController::class, 'aiSummarize']);
                Route::get('/knowledge/articles', [AdminCommunicationController::class, 'knowledgeIndex']);
                Route::post('/knowledge/articles', [AdminCommunicationController::class, 'knowledgeStore']);
                Route::get('/knowledge/categories', [AdminCommunicationController::class, 'knowledgeCategories']);
                Route::get('/visitors/live', [AdminCommunicationController::class, 'liveVisitors']);
                Route::put('/leads/{id}', [AdminCommunicationController::class, 'updateLead']);
            });

            Route::get('/announcements', [AdminController::class, 'announcements']);
            Route::post('/announcements', [AdminController::class, 'createAnnouncement']);
            Route::put('/announcements/{id}', [AdminController::class, 'updateAnnouncement']);
            Route::delete('/announcements/{id}', [AdminController::class, 'deleteAnnouncement']);

            Route::get('/platform/overview', [AdminOperationsController::class, 'overview']);
            Route::get('/platform/revenue', [AdminOperationsController::class, 'revenue']);
            Route::get('/platform/churn', [AdminOperationsController::class, 'churn']);
            Route::get('/platform/maintenance', [AdminOperationsController::class, 'maintenance']);
            Route::put('/platform/maintenance', [AdminOperationsController::class, 'updateMaintenance']);
            Route::get('/export/{type}', [AdminOperationsController::class, 'export']);

            Route::get('/health-scores', [AdminPhase2Controller::class, 'healthScores']);
            Route::get('/virtual-tour-stats', [AdminPhase2Controller::class, 'virtualTourStats']);
            Route::get('/feature-flags', [AdminPhase2Controller::class, 'featureFlags']);
            Route::put('/feature-flags/{key}', [AdminPhase2Controller::class, 'updateFeatureFlag']);
            Route::get('/commissions/kpi', [AdminPhase2Controller::class, 'commissionKpi']);
            Route::get('/crm/follow-ups', [AdminPhase2Controller::class, 'crmFollowUps']);
            Route::get('/phase2/summary', [AdminPhase2Controller::class, 'phase2Summary']);
            Route::post('/system/sms-test', [AdminPhase2Controller::class, 'testSms']);

            Route::get('/customers', [AdminDataController::class, 'customers']);
            Route::get('/owners', [AdminDataController::class, 'owners']);
            Route::get('/properties', [AdminDataController::class, 'properties']);
            Route::get('/crm-deals', [AdminDataController::class, 'crmDeals']);
            Route::get('/property-visits', [AdminDataController::class, 'propertyVisits']);
            Route::get('/visit-requests', [AdminDataController::class, 'visitRequests']);
            Route::get('/contracts', [AdminDataController::class, 'contracts']);
            Route::get('/commissions', [AdminDataController::class, 'commissions']);
            Route::get('/accounting', [AdminDataController::class, 'accounting']);
            Route::get('/devices', [AdminDataController::class, 'devices']);
            Route::get('/impersonation-sessions', [AdminDataController::class, 'impersonationSessions']);

            Route::get('/blog', [BlogAdminController::class, 'index']);
            Route::get('/blog/health', [BlogAdminController::class, 'health']);
            Route::get('/blog/dashboard', [BlogCmsAdminController::class, 'dashboard']);
            Route::get('/seo/growth', [SeoGrowthAdminController::class, 'executive']);
            Route::post('/seo/collect-gsc', [SeoGrowthAdminController::class, 'collectGsc']);
            Route::post('/seo/analyze', [SeoGrowthAdminController::class, 'analyze']);
            Route::get('/seo/opportunities', [SeoGrowthAdminController::class, 'opportunities']);
            Route::get('/seo/recommendations', [SeoGrowthAdminController::class, 'recommendations']);
            Route::post('/seo/recommendations/{id}/approve', [SeoGrowthAdminController::class, 'approveRecommendation']);
            Route::post('/seo/recommendations/{id}/reject', [SeoGrowthAdminController::class, 'rejectRecommendation']);
            Route::post('/seo/recommendations/{id}/execute', [SeoGrowthAdminController::class, 'executeRecommendation']);
            Route::post('/seo/recommendations/{id}/rollback', [SeoGrowthAdminController::class, 'rollbackRecommendation']);
            Route::get('/seo/health', [SeoGrowthAdminController::class, 'health']);
            Route::get('/seo/topics', [SeoGrowthAdminController::class, 'topics']);
            Route::get('/seo/queries', [SeoGrowthAdminController::class, 'queries']);
            Route::get('/seo/alerts', [SeoGrowthAdminController::class, 'alerts']);
            Route::post('/seo/alerts/{id}/resolve', [SeoGrowthAdminController::class, 'resolveAlert']);
            Route::get('/seo/reports/weekly', [SeoGrowthAdminController::class, 'weeklyReports']);
            Route::get('/seo/technical', [TechnicalSeoAdminController::class, 'dashboard']);
            Route::post('/seo/technical/run', [TechnicalSeoAdminController::class, 'run']);
            Route::get('/seo/technical/history', [TechnicalSeoAdminController::class, 'history']);
            Route::post('/seo/technical/validate-sitemap', [TechnicalSeoAdminController::class, 'validateSitemap']);
            Route::post('/seo/technical/broken-links/{id}/resolve', [TechnicalSeoAdminController::class, 'resolveBrokenLink']);
            Route::get('/cro/dashboard', [CroAdminController::class, 'dashboard']);
            Route::post('/cro/bootstrap', [CroAdminController::class, 'bootstrap']);
            Route::get('/cro/leads', [CroAdminController::class, 'leads']);
            Route::post('/cro/leads/{id}/status', [CroAdminController::class, 'updateLeadStatus']);
            Route::post('/cro/leads/{id}/feedback', [CroAdminController::class, 'feedback']);
            Route::get('/cro/ctas', [CroAdminController::class, 'ctas']);
            Route::post('/cro/ctas', [CroAdminController::class, 'storeCta']);
            Route::put('/cro/ctas/{id}', [CroAdminController::class, 'updateCta']);
            Route::post('/cro/cta-rules', [CroAdminController::class, 'storeRule']);
            Route::get('/cro/experiments', [CroAdminController::class, 'experiments']);
            Route::post('/cro/experiments', [CroAdminController::class, 'storeExperiment']);
            Route::post('/blog/bootstrap', [BlogCmsAdminController::class, 'bootstrap']);
            Route::get('/blog/export.csv', [BlogCmsAdminController::class, 'exportCsv']);
            Route::post('/blog/bulk', [BlogCmsAdminController::class, 'bulk']);
            Route::get('/blog/cms/categories', [BlogCmsAdminController::class, 'categories']);
            Route::post('/blog/cms/categories', [BlogCmsAdminController::class, 'storeCategory']);
            Route::put('/blog/cms/categories/{id}', [BlogCmsAdminController::class, 'updateCategory']);
            Route::get('/blog/cms/tags', [BlogCmsAdminController::class, 'tags']);
            Route::post('/blog/cms/tags', [BlogCmsAdminController::class, 'storeTag']);
            Route::put('/blog/cms/tags/{id}', [BlogCmsAdminController::class, 'updateTag']);
            Route::post('/blog/cms/tags/merge', [BlogCmsAdminController::class, 'mergeTags']);
            Route::get('/blog/cms/authors', [BlogCmsAdminController::class, 'authors']);
            Route::post('/blog/cms/authors', [BlogCmsAdminController::class, 'storeAuthor']);
            Route::put('/blog/cms/authors/{id}', [BlogCmsAdminController::class, 'updateAuthor']);
            Route::get('/blog/cms/redirects', [BlogCmsAdminController::class, 'redirects']);
            Route::post('/blog/cms/redirects', [BlogCmsAdminController::class, 'storeRedirect']);
            Route::delete('/blog/cms/redirects/{id}', [BlogCmsAdminController::class, 'deleteRedirect']);
            Route::get('/blog/categories', [BlogAdminController::class, 'categories']);
            Route::post('/blog/analyze-seo', [BlogAdminController::class, 'analyzeSeo']);
            Route::post('/blog/publish-checklist', [BlogAdminController::class, 'publishChecklist']);
            Route::get('/blog/ai/actions', [BlogAdminController::class, 'aiActions']);
            Route::post('/blog/ai/assist', [BlogAdminController::class, 'aiAssist'])->middleware('throttle:30,1');
            Route::get('/blog/calendar', [BlogCmsAdminController::class, 'calendar']);
            Route::get('/blog/media', [BlogCmsAdminController::class, 'media']);
            Route::delete('/blog/media', [BlogCmsAdminController::class, 'deleteMedia']);
            Route::post('/blog/upload-image', [BlogAdminController::class, 'uploadImage']);
            Route::post('/blog/upload-cover', [BlogAdminController::class, 'uploadCover']);
            Route::get('/blog/{id}/suggest-links', [BlogCmsAdminController::class, 'suggestLinks']);
            Route::post('/blog/{id}/publish', [BlogAdminController::class, 'publish']);
            Route::post('/blog/{id}/unpublish', [BlogAdminController::class, 'unpublish']);
            Route::post('/blog/{id}/schedule', [BlogAdminController::class, 'schedule']);
            Route::post('/blog/{id}/archive', [BlogAdminController::class, 'archive']);
            Route::post('/blog/{id}/preview-token', [BlogAdminController::class, 'previewToken']);
            Route::post('/blog/{id}/submit-review', [BlogAdminController::class, 'submitReview']);
            Route::post('/blog/{id}/approve', [BlogAdminController::class, 'approve']);
            Route::post('/blog/{id}/autosave', [BlogAdminController::class, 'autosave']);
            Route::get('/blog/{id}/autosave', [BlogAdminController::class, 'recoverAutosave']);
            Route::post('/blog/{id}/lock', [BlogAdminController::class, 'acquireLock']);
            Route::delete('/blog/{id}/lock', [BlogAdminController::class, 'releaseLock']);
            Route::post('/blog/{id}/restore-trash', [BlogAdminController::class, 'restoreTrash']);
            Route::delete('/blog/{id}/force', [BlogAdminController::class, 'forceDestroy']);
            Route::get('/blog/{id}/versions/{versionId}/compare', [BlogAdminController::class, 'compareVersions']);
            Route::post('/blog/{id}/versions/{versionId}/restore', [BlogAdminController::class, 'restoreVersion']);
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
