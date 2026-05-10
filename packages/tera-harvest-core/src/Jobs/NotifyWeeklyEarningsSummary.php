<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\DriverEarningsSummary;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class NotifyWeeklyEarningsSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $companyId, private string $periodLabel) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $summaries = DriverEarningsSummary::where('company_id', $this->companyId)
                                           ->where('period_type', 'weekly')
                                           ->where('period_label', $this->periodLabel)
                                           ->where('net_earnings_etb', '>', 0)
                                           ->get();

        foreach ($summaries as $summary) {
            $phone = DB::table('users')->where('id', $summary->driver_id)->value('phone');
            if (!$phone) {
                continue;
            }

            $net  = number_format((float) $summary->net_earnings_etb, 2);
            $rate = round($summary->onTimeRate(), 0);
            $sms->send($phone, "Weekly Earnings Summary: Br {$net} earned, {$summary->deliveries_completed} deliveries, {$rate}% on-time. Keep it up!");
        }
    }
}
