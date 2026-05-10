<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SuggestHarvestPrice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly string $listingId) {}

    public function handle(TeraHarvestAIClient $ai): void
    {
        $listing = HarvestListing::find($this->listingId);
        if (!$listing) {
            return;
        }

        try {
            $result = $ai->suggestPrice(
                $listing->crop_type,
                (float) $listing->quantity_kg,
                (string) $listing->woreda_id
            );

            $listing->update([
                'ai_suggested_price_etb' => $result['suggested_price_etb'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('SuggestHarvestPrice failed', ['listing_id' => $this->listingId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
