<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\AggregationLotContribution;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class NotifyLotContributors implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $lotId) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $contributions = AggregationLotContribution::where('lot_id', $this->lotId)
                                                    ->where('payout_status', 'paid')
                                                    ->get();

        foreach ($contributions as $contribution) {
            $phone = DB::table('users')->where('id', $contribution->farmer_id)->value('phone');
            if (!$phone) {
                continue;
            }
            $amount = number_format((float) $contribution->net_payout_etb, 2);
            $sms->send($phone, "ፍሬ ሰበሰቡ! ብር {$amount} ወደ ቦርሳዎ ተላልፏል። አመሰጋናለን!");
        }
    }
}
