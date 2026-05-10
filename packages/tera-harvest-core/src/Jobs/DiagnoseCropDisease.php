<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\DiseaseReport;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DiagnoseCropDisease implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(private string $reportId) {}

    public function handle(TeraHarvestAIClient $ai): void
    {
        $report = DiseaseReport::find($this->reportId);
        if (!$report || $report->status !== 'pending') {
            return;
        }

        try {
            $diagnosis = $ai->diagnoseCropDisease([
                'crop_type'   => $report->crop_type,
                'symptoms'    => $report->symptoms,
                'region'      => $report->region_id,
                'photo_urls'  => $report->photo_urls ?? [],
            ]);

            $report->update([
                'disease_name'           => $diagnosis['disease_name'] ?? null,
                'ai_diagnosis'           => $diagnosis,
                'ai_confidence'          => $diagnosis['confidence'] ?? null,
                'recommended_treatment'  => $diagnosis['treatment'] ?? null,
                'severity'               => $diagnosis['severity'] ?? $report->severity,
            ]);

            if (($diagnosis['severity'] ?? '') === 'critical') {
                EscalateHighSeverityDisease::dispatch($this->reportId);
            }
        } catch (\Exception $e) {
            Log::error("DiagnoseCropDisease failed for {$this->reportId}: " . $e->getMessage());
            throw $e;
        }
    }
}
