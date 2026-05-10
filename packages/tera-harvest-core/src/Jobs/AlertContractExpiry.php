<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\ForwardContract;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AlertContractExpiry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(AfricasTalkingService $sms): void
    {
        $expiring = ForwardContract::whereIn('status', ['active', 'partially_fulfilled'])
                                   ->where('delivery_end_date', '<=', now()->addDays(7))
                                   ->where('delivery_end_date', '>', now())
                                   ->get();

        foreach ($expiring as $contract) {
            $phone = DB::table('users')->where('id', $contract->buyer_id)->value('phone');
            if ($phone) {
                $days = now()->diffInDays($contract->delivery_end_date);
                $sms->send($phone, "Forward contract {$contract->contract_number} expires in {$days} day(s). Fulfillment: {$contract->fulfillmentPercentage()}%.");
            }
        }
    }
}
