<?php

namespace Fleetbase\TeraHarvest\Listeners;

use Fleetbase\TeraHarvest\Events\HarvestPriceBelowMarket;
use Fleetbase\TeraHarvest\Jobs\SendSmsNotification;
use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Illuminate\Support\Str;

class HandleHarvestPriceBelowMarket
{
    public function handle(HarvestPriceBelowMarket $event): void
    {
        $msg = NotificationMessage::create([
            'uuid'         => (string) Str::uuid(),
            'company_id'   => session('company_id', ''),
            'recipient_id' => $event->farmerId,
            'channel'      => 'sms',
            'language'     => 'am',
            'event_type'   => 'price_below_market',
            'message_am'   => "የECX ዋጋ {$event->ecxPrice} ብር ነው። የዝርዝርዎ ዋጋ: {$event->askingPrice} ብር። ለማዘመን ይሞክሩ።",
            'message_en'   => "ECX price is {$event->ecxPrice} ETB. Your listing price: {$event->askingPrice} ETB.",
            'provider'     => 'africas_talking',
            'status'       => 'queued',
        ]);

        SendSmsNotification::dispatch($msg->id);
    }
}
