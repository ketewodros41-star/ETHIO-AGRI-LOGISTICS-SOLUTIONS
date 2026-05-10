<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\PriceNegotiation;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class NotifyNegotiationTurn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $negotiationId, private string $notifyUserId) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $negotiation = PriceNegotiation::find($this->negotiationId);
        if (!$negotiation) {
            return;
        }

        $phone = DB::table('users')->where('id', $this->notifyUserId)->value('phone');
        if (!$phone) {
            return;
        }

        $offer   = number_format((float) $negotiation->current_offer_etb, 2);
        $listing = DB::table('harvest_listings')->where('id', $negotiation->listing_id)->value('commodity') ?? 'ምርት';
        $sms->send($phone, "የ{$listing} ዋጋ ድርድር: አዲስ ዋጋ ብር {$offer} ቀርቧል። ምላሽ ይስጡ።");
    }
}
