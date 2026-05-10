<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\InputOrder;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DispatchInputOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $orderId) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $order = InputOrder::find($this->orderId);
        if (!$order || $order->status !== 'pending') {
            return;
        }

        $order->update(['status' => 'confirmed']);
        NotifyInputOrderStatus::dispatch($this->orderId, 'confirmed');
    }
}
