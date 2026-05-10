<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\DiseaseReport;
use Fleetbase\TeraHarvest\Models\DiseaseAlert;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BroadcastDiseaseAlert implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $reportId) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $report = DiseaseReport::find($this->reportId);
        if (!$report) {
            return;
        }

        $alert = DiseaseAlert::create([
            'company_id'       => $report->company_id,
            'source_report_id' => $report->id,
            'crop_type'        => $report->crop_type,
            'disease_name'     => $report->disease_name ?? 'Unknown',
            'severity'         => $report->severity,
            'region_id'        => $report->region_id,
            'radius_km'        => 50.0,
            'alert_message'    => "Disease alert: {$report->disease_name} detected in {$report->crop_type}. Severity: {$report->severity}.",
            'alert_message_am' => "በሽታ ማስጠንቀቂያ: {$report->crop_type} ውስጥ {$report->disease_name} ተከስቷል።",
            'expires_at'       => now()->addDays(14),
            'escalation_status' => 'local',
        ]);

        $phoneNumbers = DB::table('users')
                          ->where('company_id', $report->company_id)
                          ->whereNotNull('phone')
                          ->pluck('phone')
                          ->toArray();

        $count = 0;
        foreach (array_chunk($phoneNumbers, 100) as $chunk) {
            $sms->sendBulk($chunk, $alert->alert_message_am);
            $count += count($chunk);
        }

        $alert->update(['farmers_notified' => $count]);
        Log::info("BroadcastDiseaseAlert: notified {$count} farmers for alert {$alert->id}");
    }
}
