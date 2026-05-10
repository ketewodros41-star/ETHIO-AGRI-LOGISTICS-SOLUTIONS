<?php

namespace Fleetbase\TeraHarvest\Listeners;

use Fleetbase\TeraHarvest\Events\DriverAssigned;
use Fleetbase\TeraHarvest\Jobs\SendSmsNotification;
use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Illuminate\Support\Str;

class HandleDriverAssigned
{
    public function handle(DriverAssigned $event): void
    {
        if (!$event->farmerId) {
            return;
        }

        $msg = NotificationMessage::create([
            'uuid'         => (string) Str::uuid(),
            'company_id'   => session('company_id', ''),
            'recipient_id' => $event->farmerId,
            'channel'      => 'sms',
            'language'     => 'am',
            'event_type'   => 'driver_assigned',
            'message_am'   => "የእርስዎ ምርት በ{$event->driverName} ይወሰዳል። ትዕዛዝ: #{$event->orderId}",
            'message_en'   => "Your produce will be picked up by {$event->driverName}. Order: #{$event->orderId}",
            'provider'     => 'africas_talking',
            'status'       => 'queued',
        ]);

        SendSmsNotification::dispatch($msg->id);
    }
}
