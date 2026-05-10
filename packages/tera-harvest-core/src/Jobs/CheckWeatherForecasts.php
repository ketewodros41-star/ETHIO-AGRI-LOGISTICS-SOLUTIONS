<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\EthiopiaRegion;
use Fleetbase\TeraHarvest\Services\WeatherMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckWeatherForecasts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 600;

    public function __construct(private string $companyId) {}

    public function handle(WeatherMonitoringService $service): void
    {
        $regions     = EthiopiaRegion::all();
        $totalAlerts = 0;

        foreach ($regions as $region) {
            $alerts = $service->checkRegion($region, $this->companyId);
            $totalAlerts += count($alerts);

            foreach ($alerts as $alert) {
                if (in_array($alert->severity, ['warning', 'emergency'])) {
                    ProcessWeatherAlert::dispatch($alert->id, $this->companyId);
                }
            }
        }

        Log::info("CheckWeatherForecasts: {$totalAlerts} alert(s) created for company {$this->companyId}");
    }
}
