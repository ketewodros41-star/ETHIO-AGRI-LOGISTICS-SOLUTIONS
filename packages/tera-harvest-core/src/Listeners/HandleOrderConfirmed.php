<?php

namespace Fleetbase\TeraHarvest\Listeners;

use Fleetbase\TeraHarvest\Events\OrderConfirmed;
use Fleetbase\TeraHarvest\Jobs\SendSmsNotification;
use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Illuminate\Support\Str;

class HandleOrderConfirmed
{
    public function handle(OrderConfirmed $event): void
    {
        foreach (array_filter([$event->farmerId, $event->cooperativeId]) as $recipientId) {
            $msg = NotificationMessage::create([
                'uuid'         => (string) Str::uuid(),
                'company_id'   => session('company_id', ''),
                'recipient_id' => $recipientId,
                'channel'      => 'sms',
                'language'     => 'am',
                'event_type'   => 'order_confirmed',
                'message_am'   => "ትዕዛዝዎ #{$event->orderId} ተረጋግጧል። ለተጨማሪ መረጃ ስልካዊ ጥቆማ ይጠብቁ።",
                'message_en'   => "Your order #{$event->orderId} has been confirmed.",
                'provider'     => 'africas_talking',
                'status'       => 'queued',
            ]);

            SendSmsNotification::dispatch($msg->id);
        }
    }
}
