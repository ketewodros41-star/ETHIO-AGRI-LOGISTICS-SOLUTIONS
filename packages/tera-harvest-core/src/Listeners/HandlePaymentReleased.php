<?php

namespace Fleetbase\TeraHarvest\Listeners;

use Fleetbase\TeraHarvest\Events\PaymentReleased;
use Fleetbase\TeraHarvest\Jobs\SendSmsNotification;
use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Illuminate\Support\Str;

class HandlePaymentReleased
{
    public function handle(PaymentReleased $event): void
    {
        $msg = NotificationMessage::create([
            'uuid'         => (string) Str::uuid(),
            'company_id'   => session('company_id', ''),
            'recipient_id' => $event->recipientId,
            'channel'      => 'sms',
            'language'     => 'am',
            'event_type'   => 'payment_released',
            'message_am'   => "{$event->amountEtb} ብር ወደ Telebirr ሂሳብዎ ተልኳል።",
            'message_en'   => "{$event->amountEtb} ETB has been sent to your Telebirr account.",
            'provider'     => 'africas_talking',
            'status'       => 'queued',
        ]);

        SendSmsNotification::dispatch($msg->id);
    }
}
