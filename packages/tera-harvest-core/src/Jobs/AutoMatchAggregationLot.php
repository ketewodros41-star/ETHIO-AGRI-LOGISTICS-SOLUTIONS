<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\AggregationLot;
use Fleetbase\TeraHarvest\Services\BulkAggregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoMatchAggregationLot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $lotId) {}

    public function handle(BulkAggregationService $service): void
    {
        $lot = AggregationLot::find($this->lotId);
        if (!$lot || $lot->status !== 'closed') {
            return;
        }

        $service->autoMatch($lot);
    }
}
