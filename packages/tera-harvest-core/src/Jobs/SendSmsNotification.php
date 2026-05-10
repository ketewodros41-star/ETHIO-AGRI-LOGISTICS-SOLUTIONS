<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\NotificationMessage;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly string $messageId) {}

    public function handle(AfricasTalkingService $at): void
    {
        $msg = NotificationMessage::find($this->messageId);
        if (!$msg || in_array($msg->status, ['sent', 'delivered'])) {
            return; // Idempotent: skip if already sent
        }

        $text = $msg->language === 'am' ? $msg->message_am : $msg->message_en;

        try {
            $result = $at->sendSms($msg->recipient_phone, $text, $msg->language);

            $msg->update([
                'status'            => 'sent',
                'sent_message'      => $text,
                'provider_response' => $result,
                'sent_at'           => now(),
                'retry_count'       => $msg->retry_count,
            ]);
        } catch (\Throwable $e) {
            $msg->increment('retry_count');
            $msg->update(['status' => 'failed']);
            Log::error('SMS send failed', ['msg_id' => $this->messageId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
