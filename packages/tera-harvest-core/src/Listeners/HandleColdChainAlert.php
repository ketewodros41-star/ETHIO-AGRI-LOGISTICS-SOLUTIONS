<?php

namespace Fleetbase\TeraHarvest\Listeners;

use Fleetbase\TeraHarvest\Events\ColdChainAlert;
use Fleetbase\TeraHarvest\Jobs\SendSmsNotification;
use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HandleColdChainAlert
{
    public function handle(ColdChainAlert $event): void
    {
        Log::warning('Cold chain threshold exceeded', [
            'shipment_id' => $event->shipmentId,
            'actual_temp' => $event->actualTemp,
            'max_temp'    => $event->maxTemp,
        ]);

        // Notify dispatcher (company_id = tenant context)
        // In production: look up dispatcher + buyer contacts for this shipment
        $msg = NotificationMessage::create([
            'uuid'         => (string) Str::uuid(),
            'company_id'   => session('company_id', ''),
            'recipient_id' => null,
            'channel'      => 'sms',
            'language'     => 'en',
            'event_type'   => 'cold_chain_alert',
            'message_en'   => "ALERT: Temperature exceeded for shipment #{$event->shipmentId}. Actual: {$event->actualTemp}°C / Max: {$event->maxTemp}°C",
            'provider'     => 'africas_talking',
            'status'       => 'queued',
        ]);
    }
}
