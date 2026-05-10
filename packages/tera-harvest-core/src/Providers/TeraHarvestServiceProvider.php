<?php

namespace Fleetbase\TeraHarvest\Providers;

use Fleetbase\TeraHarvest\Console\Commands\SeedEthiopiaData;
use Fleetbase\TeraHarvest\Events\ColdChainAlert;
use Fleetbase\TeraHarvest\Events\DeliveryConfirmed;
use Fleetbase\TeraHarvest\Events\DriverAssigned;
use Fleetbase\TeraHarvest\Events\HarvestPriceBelowMarket;
use Fleetbase\TeraHarvest\Events\OrderConfirmed;
use Fleetbase\TeraHarvest\Events\PaymentReleased;
use Fleetbase\TeraHarvest\Events\QualityCertificateReady;
// Phase 2 events
use Fleetbase\TeraHarvest\Events\CreditScoreImproved;
use Fleetbase\TeraHarvest\Events\LotSettled;
use Fleetbase\TeraHarvest\Events\DiseaseAlertBroadcast;
use Fleetbase\TeraHarvest\Events\WeatherAlertTriggered;
use Fleetbase\TeraHarvest\Events\NegotiationAccepted;
use Fleetbase\TeraHarvest\Events\ContractDeposited;
use Fleetbase\TeraHarvest\Events\YieldPredictionReady;
use Fleetbase\TeraHarvest\Events\CarbonThresholdExceeded;
use Fleetbase\TeraHarvest\Listeners\HandleColdChainAlert;
use Fleetbase\TeraHarvest\Listeners\HandleDeliveryConfirmed;
use Fleetbase\TeraHarvest\Listeners\HandleDriverAssigned;
use Fleetbase\TeraHarvest\Listeners\HandleHarvestPriceBelowMarket;
use Fleetbase\TeraHarvest\Listeners\HandleOrderConfirmed;
use Fleetbase\TeraHarvest\Listeners\HandlePaymentReleased;
use Fleetbase\TeraHarvest\Listeners\HandleQualityCertificateReady;
// Phase 2 jobs (used in scheduler)
use Fleetbase\TeraHarvest\Jobs\BulkRecalculateCreditScores;
use Fleetbase\TeraHarvest\Jobs\AlertContractExpiry;
use Fleetbase\TeraHarvest\Jobs\MatchSubscriptionToListings;
use Fleetbase\TeraHarvest\Jobs\ExpireStaleNegotiations;
use Fleetbase\TeraHarvest\Jobs\NotifyUpcomingHarvest;
use Fleetbase\TeraHarvest\Jobs\GenerateRegionalLeaderboard;
use Fleetbase\TeraHarvest\Jobs\NotifyWeeklyEarningsSummary;
use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Models\PaymentWallet;
use Fleetbase\TeraHarvest\Policies\HarvestListingPolicy;
use Fleetbase\TeraHarvest\Policies\PaymentWalletPolicy;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class TeraHarvestServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/tera_harvest.php', 'tera_harvest');

        $this->app->singleton(TeraHarvestAIClient::class, function ($app) {
            return new TeraHarvestAIClient(
                config('tera_harvest.ai_service_url'),
                config('tera_harvest.ai_service_timeout')
            );
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'tera-harvest');
        $this->loadTranslationsFrom(__DIR__ . '/../../resources/lang', 'tera-harvest');

        $this->publishes([
            __DIR__ . '/../../config/tera_harvest.php' => config_path('tera_harvest.php'),
        ], 'tera-harvest-config');

        Gate::policy(HarvestListing::class, HarvestListingPolicy::class);
        Gate::policy(PaymentWallet::class, PaymentWalletPolicy::class);

        // Phase 1 event listeners
        Event::listen(OrderConfirmed::class,           HandleOrderConfirmed::class);
        Event::listen(DriverAssigned::class,            HandleDriverAssigned::class);
        Event::listen(DeliveryConfirmed::class,         HandleDeliveryConfirmed::class);
        Event::listen(PaymentReleased::class,            HandlePaymentReleased::class);
        Event::listen(HarvestPriceBelowMarket::class,   HandleHarvestPriceBelowMarket::class);
        Event::listen(QualityCertificateReady::class,   HandleQualityCertificateReady::class);
        Event::listen(ColdChainAlert::class,             HandleColdChainAlert::class);

        // Phase 2 events are fired explicitly by jobs/services (no additional listeners needed
        // beyond what jobs already dispatch). Events are registered here for discoverability.
        Event::listen(CreditScoreImproved::class,       fn() => null);
        Event::listen(LotSettled::class,                fn() => null);
        Event::listen(DiseaseAlertBroadcast::class,     fn() => null);
        Event::listen(WeatherAlertTriggered::class,     fn() => null);
        Event::listen(NegotiationAccepted::class,       fn() => null);
        Event::listen(ContractDeposited::class,         fn() => null);
        Event::listen(YieldPredictionReady::class,      fn() => null);
        Event::listen(CarbonThresholdExceeded::class,   fn() => null);

        if ($this->app->runningInConsole()) {
            $this->commands([SeedEthiopiaData::class]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            // Phase 1 schedules
            $schedule->command('tera-harvest:expire-listings')->dailyAt('01:00');
            $schedule->command('tera-harvest:disburse-payments')->dailyAt('02:00');
            $schedule->command('tera-harvest:reconcile-payments')->dailyAt('03:00');
            $schedule->command('tera-harvest:check-document-expiry')->dailyAt('06:00');
            $schedule->command('tera-harvest:apply-seasonal-routes')->weeklyOn(1, '04:00');
            $schedule->command('tera-harvest:analytics-snapshot')->weeklyOn(0, '00:00');
            $schedule->command('tera-harvest:weekly-insight-report')->weeklyOn(1, '06:00');
            // Phase 2 schedules
            $schedule->call(fn() => AlertContractExpiry::dispatch())->dailyAt('07:00');
            $schedule->call(fn() => ExpireStaleNegotiations::dispatch())->everyThirtyMinutes();
            $schedule->call(fn() => NotifyUpcomingHarvest::dispatch())->dailyAt('08:00');
        });
    }
}
