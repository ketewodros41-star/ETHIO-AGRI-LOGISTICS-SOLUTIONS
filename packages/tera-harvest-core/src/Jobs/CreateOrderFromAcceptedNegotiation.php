<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\PriceNegotiation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateOrderFromAcceptedNegotiation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $negotiationId) {}

    public function handle(): void
    {
        $negotiation = PriceNegotiation::find($this->negotiationId);
        if (!$negotiation || $negotiation->status !== 'accepted') {
            return;
        }

        if ($negotiation->order_id) {
            return;
        }

        $orderId = DB::table('orders')->insertGetId([
            'id'           => \Illuminate\Support\Str::ulid(),
            'company_id'   => $negotiation->company_id,
            'buyer_id'     => $negotiation->buyer_id,
            'seller_id'    => $negotiation->seller_id,
            'listing_id'   => $negotiation->listing_id,
            'quantity_kg'  => $negotiation->quantity_kg,
            'price_per_kg' => $negotiation->agreed_price_etb,
            'total_etb'    => bcmul((string) $negotiation->quantity_kg, (string) $negotiation->agreed_price_etb, 2),
            'status'       => 'confirmed',
            'source'       => 'negotiation',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $negotiation->update(['status' => 'converted_to_order', 'order_id' => $orderId]);
        Log::info("Order #{$orderId} created from accepted negotiation {$this->negotiationId}");
    }
}
