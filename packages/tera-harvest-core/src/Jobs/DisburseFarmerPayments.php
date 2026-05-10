<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\PaymentWallet;
use Fleetbase\TeraHarvest\Services\Payments\TelebirrPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DisburseFarmerPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TelebirrPaymentService $telebirr): void
    {
        // Find wallets with positive balance pending disbursement
        $wallets = PaymentWallet::where('status', 'active')
            ->where('balance_etb', '>', '0.00')
            ->get();

        $payments = $wallets->map(fn ($w) => [
            'phone'     => $w->owner?->phone ?? '',
            'amount'    => $w->balance_etb,
            'reference' => 'TH-DISB-' . $w->id,
        ])->filter(fn ($p) => !empty($p['phone']))->values()->all();

        if (empty($payments)) {
            return;
        }

        try {
            $telebirr->bulkDisbursement($payments);
        } catch (\Throwable $e) {
            Log::error('DisburseFarmerPayments failed', ['error' => $e->getMessage()]);
        }
    }
}
