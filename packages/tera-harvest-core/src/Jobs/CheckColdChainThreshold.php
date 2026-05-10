<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Events\ColdChainAlert;
use Fleetbase\TeraHarvest\Models\ColdChainLog;
use Fleetbase\TeraHarvest\Models\HarvestListing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckColdChainThreshold implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $logId) {}

    public function handle(): void
    {
        $log  = ColdChainLog::findOrFail($this->logId);

        // Determine crop type from the related shipment/listing
        // Fallback: generic threshold
        $maxTemp = config('tera_harvest.cold_chain.vegetables_max_temp_c', 8);

        if ((float) $log->temperature_celsius > $maxTemp) {
            $log->update(['alert_triggered' => true]);
            ColdChainAlert::dispatch($log->shipment_id, (float) $log->temperature_celsius, $maxTemp);
        }
    }
}
