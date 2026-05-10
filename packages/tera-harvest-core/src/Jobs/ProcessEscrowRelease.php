<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Events\PaymentReleased;
use Fleetbase\TeraHarvest\Models\EscrowHold;
use Fleetbase\TeraHarvest\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessEscrowRelease implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $holdId,
        public readonly string $releaseTrigger,
        public readonly ?string $releasedBy = null
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $hold = EscrowHold::where('id', $this->holdId)->where('status', 'held')->lockForUpdate()->first();
            if (!$hold) {
                return; // Already released or not found — idempotent
            }

            $sellerWallet = $hold->sellerWallet()->lockForUpdate()->first();
            $buyerWallet  = $hold->buyerWallet()->lockForUpdate()->first();

            // Release funds to seller (use bcmath — never float)
            $netToSeller = bcsub(
                bcsub($hold->amount_etb, $hold->driver_commission_etb, 2),
                $hold->broker_commission_etb,
                2
            );

            $sellerWallet->update([
                'balance_etb' => bcadd($sellerWallet->balance_etb, $netToSeller, 2),
            ]);

            $buyerWallet->update([
                'reserved_etb' => bcsub($buyerWallet->reserved_etb, $hold->amount_etb, 2),
            ]);

            $hold->update([
                'status'          => 'released',
                'released_at'     => now(),
                'release_trigger' => $this->releaseTrigger,
                'released_by'     => $this->releasedBy,
            ]);

            // Log to immutable ledger
            $tx = PaymentTransaction::create([
                'uuid'        => (string) \Illuminate\Support\Str::uuid(),
                'wallet_id'   => $sellerWallet->id,
                'order_id'    => $hold->order_id,
                'type'        => 'escrow_release',
                'amount_etb'  => $netToSeller,
                'fee_etb'     => '0.00',
                'provider'    => 'internal',
                'status'      => 'completed',
            ]);

            $tx->appendEvent('escrow_released', [
                'hold_id'         => $hold->id,
                'amount_etb'      => $netToSeller,
                'release_trigger' => $this->releaseTrigger,
                'released_by'     => $this->releasedBy,
            ]);

            PaymentReleased::dispatch($hold->order_id, $netToSeller, $sellerWallet->owner_id);
        });
    }
}
