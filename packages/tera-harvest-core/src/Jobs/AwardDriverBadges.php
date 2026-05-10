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

class AwardDriverBadges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $summaryId) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $summary = DriverEarningsSummary::find($this->summaryId);
        if (!$summary || empty($summary->badges_earned)) {
            return;
        }

        $phone = DB::table('users')->where('id', $summary->driver_id)->value('phone');
        if (!$phone) {
            return;
        }

        $badgeNames = implode(', ', array_map(fn($b) => ucwords(str_replace('_', ' ', $b)), $summary->badges_earned));
        $sms->send($phone, "Congratulations! You earned badge(s) this period: {$badgeNames}. Keep up the great work!");
    }
}
