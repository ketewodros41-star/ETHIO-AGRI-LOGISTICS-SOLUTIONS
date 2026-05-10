<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Services\CarbonTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMonthlyCarbonReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private string $companyId,
        private int    $year,
        private int    $month
    ) {}

    public function handle(CarbonTrackingService $service): void
    {
        $report = $service->generateMonthlyReport($this->companyId, $this->year, $this->month);
        Log::info("CarbonCreditReport generated for {$this->companyId} {$this->year}-{$this->month}: {$report->total_co2e_kg} kg CO2e");
    }
}
