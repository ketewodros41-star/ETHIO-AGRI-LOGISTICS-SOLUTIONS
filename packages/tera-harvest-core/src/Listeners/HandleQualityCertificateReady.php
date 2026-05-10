<?php

namespace Fleetbase\TeraHarvest\Listeners;

use Fleetbase\TeraHarvest\Events\QualityCertificateReady;
use Fleetbase\TeraHarvest\Jobs\SendSmsNotification;
use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Illuminate\Support\Str;

class HandleQualityCertificateReady
{
    public function handle(QualityCertificateReady $event): void
    {
        $grade = $event->grade;
        $recipientId = $grade->inspector_id ?? $grade->created_by;
        if (!$recipientId) {
            return;
        }

        $msg = NotificationMessage::create([
            'uuid'         => (string) Str::uuid(),
            'company_id'   => $grade->company_id,
            'recipient_id' => $recipientId,
            'channel'      => 'sms',
            'language'     => 'am',
            'event_type'   => 'certificate_ready',
            'message_am'   => "የጥራት ምስክርነት {$grade->certificate_number} ተዘጋጅቷል።",
            'message_en'   => "Quality certificate {$grade->certificate_number} is ready.",
            'provider'     => 'africas_talking',
            'status'       => 'queued',
        ]);

        SendSmsNotification::dispatch($msg->id);
    }
}
