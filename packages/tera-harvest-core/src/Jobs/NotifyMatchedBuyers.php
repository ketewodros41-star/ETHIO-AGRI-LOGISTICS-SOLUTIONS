<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyMatchedBuyers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $listingId) {}

    public function handle(AfricasTalkingService $at): void
    {
        $listing = HarvestListing::with('woreda')->find($this->listingId);
        if (!$listing) {
            return;
        }

        // Placeholder: query buyers with matching saved-search alerts
        // In production: query a buyer_alerts table for matching crop_type + region
    }
}
