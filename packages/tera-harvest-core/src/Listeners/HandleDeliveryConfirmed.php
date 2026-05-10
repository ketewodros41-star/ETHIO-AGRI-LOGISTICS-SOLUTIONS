<?php

namespace Fleetbase\TeraHarvest\Listeners;

use Fleetbase\TeraHarvest\Events\DeliveryConfirmed;
use Fleetbase\TeraHarvest\Jobs\ProcessEscrowRelease;
use Fleetbase\TeraHarvest\Jobs\SendSmsNotification;
use Fleetbase\TeraHarvest\Models\EscrowHold;
use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Illuminate\Support\Str;

class HandleDeliveryConfirmed
{
    public function handle(DeliveryConfirmed $event): void
    {
        $hold = EscrowHold::where('order_id', $event->orderId)->where('status', 'held')->first();

        if ($hold) {
            ProcessEscrowRelease::dispatch($hold->id, 'delivery_confirmed');
        }

        if ($event->farmerId) {
            $msg = NotificationMessage::create([
                'uuid'         => (string) Str::uuid(),
                'company_id'   => session('company_id', ''),
                'recipient_id' => $event->farmerId,
                'channel'      => 'sms',
                'language'     => 'am',
                'event_type'   => 'delivery_confirmed',
                'message_am'   => "ምርቶ ደርሷል። ክፍያ ከ 48 ሰዓታት ውስጥ ይደርስዎታል።",
                'message_en'   => "Delivery confirmed. Payment arriving within 48 hours.",
                'provider'     => 'africas_talking',
                'status'       => 'queued',
            ]);

            SendSmsNotification::dispatch($msg->id);
        }
    }
}
