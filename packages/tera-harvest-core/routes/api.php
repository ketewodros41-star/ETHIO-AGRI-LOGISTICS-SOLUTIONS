<?php

use Fleetbase\TeraHarvest\Http\Controllers\AnalyticsController;
use Fleetbase\TeraHarvest\Http\Controllers\ComplianceController;
use Fleetbase\TeraHarvest\Http\Controllers\EthiopiaController;
use Fleetbase\TeraHarvest\Http\Controllers\HarvestListingController;
use Fleetbase\TeraHarvest\Http\Controllers\NotificationController;
use Fleetbase\TeraHarvest\Http\Controllers\PaymentController;
use Fleetbase\TeraHarvest\Http\Controllers\QualityController;
use Fleetbase\TeraHarvest\Http\Controllers\RouteController;
// Phase 2 controllers
use Fleetbase\TeraHarvest\Http\Controllers\CreditController;
use Fleetbase\TeraHarvest\Http\Controllers\AggregationController;
use Fleetbase\TeraHarvest\Http\Controllers\InputSupplyController;
use Fleetbase\TeraHarvest\Http\Controllers\ContractController;
use Fleetbase\TeraHarvest\Http\Controllers\DiseaseController;
use Fleetbase\TeraHarvest\Http\Controllers\WeatherController;
use Fleetbase\TeraHarvest\Http\Controllers\PriceNegotiationController;
use Fleetbase\TeraHarvest\Http\Controllers\YieldController;
use Fleetbase\TeraHarvest\Http\Controllers\GovernmentApiController;
use Fleetbase\TeraHarvest\Http\Controllers\GovApiKeyController;
use Fleetbase\TeraHarvest\Http\Controllers\NgoProgrammeController;
use Fleetbase\TeraHarvest\Http\Controllers\CarbonController;
use Fleetbase\TeraHarvest\Http\Controllers\DriverEarningsController;
use Fleetbase\TeraHarvest\Http\Middleware\EnsureTeraHarvestTenant;
use Fleetbase\TeraHarvest\Http\Middleware\EnsureGovernmentApiKey;
use Illuminate\Support\Facades\Route;

// ─── Public / Webhook endpoints (no auth) ────────────────────────────────────
Route::prefix('v1')->group(function () {
    Route::post('payments/webhook/chapa',   [PaymentController::class, 'chapaWebhook']);
    Route::post('payments/webhook/telebirr',[PaymentController::class, 'telebirrWebhook']);
    Route::post('ussd/callback',            [NotificationController::class, 'ussdCallback']);
    Route::get('compliance/documents/{id}/verify', [ComplianceController::class, 'verify']);
    Route::get('quality/grades/{id}/verify',       [QualityController::class, 'show']);
});

// ─── Authenticated + tenant-scoped routes ────────────────────────────────────
Route::prefix('v1')
    ->middleware(['auth:sanctum', EnsureTeraHarvestTenant::class])
    ->group(function () {

        // Extension 1 — Ethiopian Administrative Hierarchy
        Route::prefix('ethiopia')->group(function () {
            Route::get('regions',                         [EthiopiaController::class, 'regions']);
            Route::get('regions/{id}/zones',              [EthiopiaController::class, 'zones']);
            Route::get('zones/{id}/woredas',              [EthiopiaController::class, 'woredas']);
            Route::get('woredas/{id}/kebeles',            [EthiopiaController::class, 'kebeles']);
            Route::get('landmarks',                       [EthiopiaController::class, 'landmarks']);
            Route::post('landmarks',                      [EthiopiaController::class, 'storeLandmark']);
            Route::post('landmarks/{id}/confirm',         [EthiopiaController::class, 'confirmLandmark']);
            Route::post('landmarks/resolve',              [EthiopiaController::class, 'resolveLandmark']);
        });

        // Extension 2 — Harvest Listings
        Route::prefix('harvest')->group(function () {
            Route::get('listings',                        [HarvestListingController::class, 'index']);
            Route::post('listings',                       [HarvestListingController::class, 'store']);
            Route::get('listings/{id}',                   [HarvestListingController::class, 'show']);
            Route::patch('listings/{id}',                 [HarvestListingController::class, 'update']);
            Route::delete('listings/{id}',                [HarvestListingController::class, 'destroy']);
            Route::post('listings/{id}/photos',           [HarvestListingController::class, 'uploadPhoto']);
            Route::delete('listings/{id}/photos/{photoId}', [HarvestListingController::class, 'deletePhoto']);
            Route::get('listings/{id}/price-intelligence',[HarvestListingController::class, 'priceIntelligence']);
            Route::post('listings/{id}/match',            [HarvestListingController::class, 'match']);
        });

        // Extension 3 — Payments
        Route::prefix('payments')->group(function () {
            Route::post('initiate',                       [PaymentController::class, 'initiate']);
            Route::get('wallets/me',                      [PaymentController::class, 'myWallet']);
            Route::get('wallets/{id}/transactions',       [PaymentController::class, 'walletTransactions']);
            Route::get('escrow/{order_id}',               [PaymentController::class, 'escrowStatus']);
            Route::post('escrow/{order_id}/release',      [PaymentController::class, 'releaseEscrow']);
            Route::post('escrow/{order_id}/dispute',      [PaymentController::class, 'disputeEscrow']);
            Route::get('ledger/{order_id}',               [PaymentController::class, 'ledger'])->name('payments.ledger');
        });

        // Extension 4 — Quality Grading
        Route::prefix('quality')->group(function () {
            Route::post('grades',                         [QualityController::class, 'store']);
            Route::get('grades/{id}',                     [QualityController::class, 'show']);
            Route::get('grades/{id}/certificate',         [QualityController::class, 'certificate']);
            Route::post('grades/{id}/dispute',            [QualityController::class, 'dispute']);
        });
        Route::prefix('shipments')->group(function () {
            Route::get('{id}/cold-chain',                 [QualityController::class, 'coldChainLog']);
        });
        Route::post('cold-chain/reading',                 [QualityController::class, 'sensorReading']);

        // Extension 6 — Notifications
        Route::prefix('sms')->group(function () {
            Route::post('send',                           [NotificationController::class, 'sendSms']);
        });
        Route::get('notifications',                       [NotificationController::class, 'index']);
        Route::patch('notifications/{id}/read',           [NotificationController::class, 'markRead']);

        // Extension 7 — Route Intelligence
        Route::prefix('routes')->group(function () {
            Route::get('segments',                        [RouteController::class, 'segments']);
            Route::get('recommend',                       [RouteController::class, 'recommend']);
            Route::post('reports',                        [RouteController::class, 'storeReport']);
            Route::get('reports',                         [RouteController::class, 'reports']);
            Route::post('history',                        [RouteController::class, 'storeHistory']);
        });

        // Extension 8 — Compliance Documents
        Route::prefix('compliance')->group(function () {
            Route::post('documents/generate',             [ComplianceController::class, 'generate']);
            Route::get('documents/{id}',                  [ComplianceController::class, 'show']);
            Route::get('documents/{id}/download',         [ComplianceController::class, 'download']);
            Route::get('checklists/{order_id}',           [ComplianceController::class, 'checklist']);
            Route::post('checklists/{order_id}/run-check',[ComplianceController::class, 'runCheck']);
            Route::get('documents/bundle/{order_id}',     [ComplianceController::class, 'bundle']);
        });

        // Extension 9 — Analytics
        Route::prefix('analytics')->group(function () {
            Route::get('supply/by-crop',                  [AnalyticsController::class, 'supplyByCrop']);
            Route::get('supply/heatmap',                  [AnalyticsController::class, 'supplyHeatmap']);
            Route::get('prices/history',                  [AnalyticsController::class, 'priceHistory']);
            Route::get('orders/summary',                  [AnalyticsController::class, 'ordersSummary']);
            Route::get('drivers/performance',             [AnalyticsController::class, 'driverPerformance']);
            Route::get('routes/bottlenecks',              [AnalyticsController::class, 'routeBottlenecks']);
            Route::get('payments/summary',                [AnalyticsController::class, 'paymentsSummary']);
            Route::get('farmers/income',                  [AnalyticsController::class, 'farmerIncome']);
            Route::get('export/pdf',                      [AnalyticsController::class, 'exportPdf']);
            Route::get('export/excel',                    [AnalyticsController::class, 'exportExcel']);
        });

        // Extension 11 — Farmer Credit Scoring
        Route::prefix('credit')->group(function () {
            Route::get('{farmerId}',                      [CreditController::class, 'show']);
            Route::get('{farmerId}/history',              [CreditController::class, 'history']);
            Route::post('{farmerId}/recalculate',         [CreditController::class, 'recalculate']);
            Route::get('{farmerId}/explain',              [CreditController::class, 'explain']);
            Route::get('{farmerId}/microfinance-package', [CreditController::class, 'microfinancePackage']);
            Route::post('requests',                       [CreditController::class, 'storeCreditRequest']);
            Route::patch('requests/{id}',                 [CreditController::class, 'updateCreditRequestStatus']);
        });

        // Extension 12 — Bulk Aggregation
        Route::prefix('aggregation/lots')->group(function () {
            Route::get('/',                               [AggregationController::class, 'index']);
            Route::post('/',                              [AggregationController::class, 'store']);
            Route::get('{id}',                            [AggregationController::class, 'show']);
            Route::post('{id}/contributions',             [AggregationController::class, 'addContribution']);
            Route::post('{id}/settle',                    [AggregationController::class, 'settle']);
        });

        // Extension 13 — Input Supply
        Route::prefix('input-supply')->group(function () {
            Route::get('suppliers',                       [InputSupplyController::class, 'listSuppliers']);
            Route::post('suppliers',                      [InputSupplyController::class, 'storeSupplier']);
            Route::get('suppliers/{id}/products',         [InputSupplyController::class, 'listProducts']);
            Route::post('suppliers/{id}/products',        [InputSupplyController::class, 'storeProduct']);
            Route::post('orders',                         [InputSupplyController::class, 'placeOrder']);
            Route::get('orders/{id}',                     [InputSupplyController::class, 'showOrder']);
        });

        // Extension 14 — Forward Contracts + Subscriptions
        Route::prefix('contracts')->group(function () {
            Route::get('/',                               [ContractController::class, 'index']);
            Route::post('/',                              [ContractController::class, 'store']);
            Route::get('{id}',                            [ContractController::class, 'show']);
            Route::post('{id}/deposit',                   [ContractController::class, 'payDeposit']);
            Route::post('{id}/cancel',                    [ContractController::class, 'cancel']);
        });
        Route::prefix('subscriptions')->group(function () {
            Route::get('/',                               [ContractController::class, 'listSubscriptions']);
            Route::post('/',                              [ContractController::class, 'storeSubscription']);
        });

        // Extension 15 — Disease Early Warning
        Route::prefix('disease')->group(function () {
            Route::get('reports',                         [DiseaseController::class, 'index']);
            Route::post('reports',                        [DiseaseController::class, 'store']);
            Route::get('reports/{id}',                    [DiseaseController::class, 'show']);
            Route::post('reports/{id}/verify',            [DiseaseController::class, 'verify']);
            Route::get('alerts',                          [DiseaseController::class, 'alerts']);
        });

        // Extension 16 — Weather Alerts
        Route::prefix('weather')->group(function () {
            Route::get('alerts',                          [WeatherController::class, 'index']);
            Route::post('check/{regionId}',               [WeatherController::class, 'checkRegion']);
            Route::get('alerts/{alertId}/impacts',        [WeatherController::class, 'impacts']);
            Route::post('check/all',                      [WeatherController::class, 'triggerCheck']);
        });

        // Extension 17 — Price Negotiation
        Route::prefix('negotiations')->group(function () {
            Route::get('/',                               [PriceNegotiationController::class, 'index']);
            Route::post('/',                              [PriceNegotiationController::class, 'initiate']);
            Route::post('{id}/respond',                   [PriceNegotiationController::class, 'respond']);
            Route::post('{id}/accept',                    [PriceNegotiationController::class, 'accept']);
            Route::get('{id}/ai-suggestion',              [PriceNegotiationController::class, 'aiSuggestion']);
        });

        // Extension 18 — Yield Prediction
        Route::prefix('yield')->group(function () {
            Route::get('plots',                           [YieldController::class, 'listPlots']);
            Route::post('plots',                          [YieldController::class, 'storePlot']);
            Route::get('plots/{id}',                      [YieldController::class, 'showPlot']);
            Route::get('predictions',                     [YieldController::class, 'listPredictions']);
            Route::post('predictions',                    [YieldController::class, 'requestPrediction']);
            Route::patch('predictions/{id}/actual',       [YieldController::class, 'recordActualYield']);
        });

        // Extension 19 — Government API Key admin
        Route::prefix('gov-api-keys')->group(function () {
            Route::get('/',                               [GovApiKeyController::class, 'index']);
            Route::post('/',                              [GovApiKeyController::class, 'store']);
            Route::patch('{id}/revoke',                   [GovApiKeyController::class, 'revoke']);
            Route::get('{id}/stats',                      [GovApiKeyController::class, 'usageStats']);
        });

        // Extension 20 — NGO Programmes
        Route::prefix('ngo/programmes')->group(function () {
            Route::get('/',                               [NgoProgrammeController::class, 'index']);
            Route::post('/',                              [NgoProgrammeController::class, 'store']);
            Route::get('{id}',                            [NgoProgrammeController::class, 'show']);
            Route::post('{id}/enrol',                     [NgoProgrammeController::class, 'enrolBeneficiary']);
            Route::post('{id}/snapshot',                  [NgoProgrammeController::class, 'generateSnapshot']);
            Route::get('{id}/snapshots',                  [NgoProgrammeController::class, 'snapshots']);
        });

        // Extension 21 — Carbon Tracking
        Route::prefix('carbon')->group(function () {
            Route::get('reports',                         [CarbonController::class, 'reports']);
            Route::post('reports/generate',               [CarbonController::class, 'generateReport']);
            Route::post('emissions',                      [CarbonController::class, 'logEmission']);
            Route::get('summary',                         [CarbonController::class, 'summary']);
        });

        // Extension 22 — Driver Earnings
        Route::prefix('driver-earnings')->group(function () {
            Route::get('{driverId}',                      [DriverEarningsController::class, 'earnings']);
            Route::get('leaderboard/current',             [DriverEarningsController::class, 'leaderboard']);
            Route::post('generate',                       [DriverEarningsController::class, 'generateSummary']);
            Route::get('disbursements/pending',           [DriverEarningsController::class, 'pendingDisbursements']);
        });
    });

// ─── Government Data API (separate auth via gov API key) ─────────────────────
Route::prefix('gov/v1')
    ->middleware([EnsureGovernmentApiKey::class])
    ->group(function () {
        Route::get('supply-overview',   [GovernmentApiController::class, 'supplyOverview']);
        Route::get('farmer-stats',      [GovernmentApiController::class, 'farmerStats']);
        Route::get('trade-volume',      [GovernmentApiController::class, 'tradeVolume']);
        Route::get('disease-alerts',    [GovernmentApiController::class, 'diseaseAlerts']);
        Route::get('carbon-summary',    [GovernmentApiController::class, 'carbonSummary']);
    });
