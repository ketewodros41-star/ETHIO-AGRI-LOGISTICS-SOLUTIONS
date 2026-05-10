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

class NotifyInputOrderStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $orderId, private string $status) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $order = InputOrder::find($this->orderId);
        if (!$order) {
            return;
        }

        $phone = DB::table('users')->where('id', $order->farmer_id)->value('phone');
        if (!$phone) {
            return;
        }

        $messages = [
            'confirmed'  => "የፍሬ ትዕዛዝዎ {$order->order_number} ተቀብሏል። ቶሎ ይደርሳሉ።",
            'dispatched' => "ትዕዛዝዎ {$order->order_number} ተላልፏል። ቶሎ ይደርሳሉ።",
            'delivered'  => "ትዕዛዝዎ {$order->order_number} ተደርሷል። ምሰጋና!",
            'cancelled'  => "ትዕዛዝዎ {$order->order_number} ተሰርዟል። ለጥያቄዎ ያነጋግሩን።",
        ];

        $message = $messages[$this->status] ?? "ትዕዛዝዎ {$order->order_number} ሁኔታ: {$this->status}";
        $sms->send($phone, $message);
    }
}
