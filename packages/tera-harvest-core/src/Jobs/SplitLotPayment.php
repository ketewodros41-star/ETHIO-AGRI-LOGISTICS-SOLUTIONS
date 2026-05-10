<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\AggregationLot;
use Fleetbase\TeraHarvest\Services\BulkAggregationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SplitLotPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private string $lotId, private string $totalSaleEtb) {}

    public function handle(BulkAggregationService $service): void
    {
        $lot = AggregationLot::find($this->lotId);
        if (!$lot) {
            return;
        }
        $service->settleLot($lot, $this->totalSaleEtb);
        NotifyLotContributors::dispatch($this->lotId);
    }
}
