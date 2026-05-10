<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\ForwardContract;
use Fleetbase\TeraHarvest\Services\ForwardContractService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessContractDeposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $contractId, private string $transactionId) {}

    public function handle(ForwardContractService $service): void
    {
        $contract = ForwardContract::find($this->contractId);
        if (!$contract || $contract->deposit_status === 'paid') {
            return;
        }

        try {
            $service->processDeposit($contract, $this->transactionId);
            Log::info("Forward contract deposit processed: {$this->contractId}");
        } catch (\Exception $e) {
            Log::error("ProcessContractDeposit failed: " . $e->getMessage());
            throw $e;
        }
    }
}
