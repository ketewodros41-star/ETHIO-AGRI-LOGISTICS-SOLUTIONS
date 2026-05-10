<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\FarmerCreditScore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkRecalculateCreditScores implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 3600;

    public function __construct(private string $companyId) {}

    public function handle(): void
    {
        $farmerIds = FarmerCreditScore::where('company_id', $this->companyId)
                                      ->pluck('farmer_id');

        $count = 0;
        foreach ($farmerIds as $farmerId) {
            RecalculateFarmerCreditScore::dispatch($farmerId, $this->companyId, 'bulk_recalculation');
            $count++;
        }

        Log::info("BulkRecalculateCreditScores: dispatched {$count} jobs for company {$this->companyId}");
    }
}
