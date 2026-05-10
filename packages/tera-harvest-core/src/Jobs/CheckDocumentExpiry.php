<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\ComplianceDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckDocumentExpiry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Mark as expired
        ComplianceDocument::where('status', 'issued')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        // Flag expiring within 7 days (notification hook — logged for now)
        $expiringSoon = ComplianceDocument::where('status', 'issued')
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->get();

        foreach ($expiringSoon as $doc) {
            \Illuminate\Support\Facades\Log::warning('Compliance document expiring soon', [
                'document_number' => $doc->document_number,
                'expires_at'      => $doc->expires_at,
                'order_id'        => $doc->order_id,
            ]);
        }
    }
}
