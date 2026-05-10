<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\DiseaseReport;
use Fleetbase\TeraHarvest\Models\DiseaseAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EscalateHighSeverityDisease implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $reportId) {}

    public function handle(): void
    {
        $report = DiseaseReport::find($this->reportId);
        if (!$report) {
            return;
        }

        DiseaseAlert::where('source_report_id', $this->reportId)
                    ->update([
                        'escalation_status'  => 'national',
                        'government_notified' => true,
                        'radius_km'           => 200.0,
                    ]);

        Log::warning("CRITICAL disease escalated to national level: {$report->disease_name} in {$report->crop_type} ({$this->reportId})");
    }
}
