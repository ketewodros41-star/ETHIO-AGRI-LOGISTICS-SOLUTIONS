<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Models\PaymentTransaction;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateWeeklyInsightReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $tenantId) {}

    public function handle(TeraHarvestAIClient $ai): void
    {
        $weekAgo = now()->subWeek();

        $dataSummary = [
            'new_listings'      => HarvestListing::where('company_id', $this->tenantId)->where('created_at', '>=', $weekAgo)->count(),
            'payment_volume'    => PaymentTransaction::where('status', 'completed')->where('created_at', '>=', $weekAgo)->sum('amount_etb'),
        ];

        try {
            $report = $ai->generateInsightReport($this->tenantId, 'weekly', $dataSummary);
            Log::info('WeeklyInsightReport generated', ['tenant_id' => $this->tenantId, 'report' => $report]);
        } catch (\Throwable $e) {
            Log::error('GenerateWeeklyInsightReport failed', ['error' => $e->getMessage()]);
        }
    }
}
