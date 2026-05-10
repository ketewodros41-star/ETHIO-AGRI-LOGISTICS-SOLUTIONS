<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\BuyerSubscription;
use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class MatchSubscriptionToListings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private string $companyId) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $subscriptions = BuyerSubscription::active()
                                           ->where('company_id', $this->companyId)
                                           ->get();

        foreach ($subscriptions as $subscription) {
            $listings = HarvestListing::active()
                                       ->where('company_id', $this->companyId)
                                       ->where('commodity', $subscription->commodity)
                                       ->when($subscription->quality_grade !== 'any', fn($q) => $q->where('quality_grade', $subscription->quality_grade))
                                       ->when($subscription->max_price_per_kg_etb, fn($q) => $q->where('price_per_kg_etb', '<=', $subscription->max_price_per_kg_etb))
                                       ->count();

            if ($listings > 0) {
                $phone = DB::table('users')->where('id', $subscription->buyer_id)->value('phone');
                if ($phone) {
                    $sms->send($phone, "{$listings} new {$subscription->commodity} listing(s) match your subscription. Check the app for details.");
                }
                $subscription->update(['last_notified_at' => now()]);
            }
        }
    }
}
