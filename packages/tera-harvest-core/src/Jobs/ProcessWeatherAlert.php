<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\WeatherAlert;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessWeatherAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $alertId, private string $companyId) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $alert = WeatherAlert::find($this->alertId);
        if (!$alert || !$alert->is_active) {
            return;
        }

        $phones = DB::table('users')
                    ->where('company_id', $this->companyId)
                    ->whereNotNull('phone')
                    ->pluck('phone')
                    ->toArray();

        $alertType = strtoupper(str_replace('_', ' ', $alert->alert_type));
        $message   = "Weather Alert ({$alert->severity}): {$alertType} expected. Stay safe and secure your harvest.";

        $count = 0;
        foreach (array_chunk($phones, 100) as $chunk) {
            $sms->sendBulk($chunk, $message);
            $count += count($chunk);
        }

        $alert->update(['farmers_notified' => $count]);
        RerouteAtRiskShipments::dispatch($this->alertId, $this->companyId);
    }
}
