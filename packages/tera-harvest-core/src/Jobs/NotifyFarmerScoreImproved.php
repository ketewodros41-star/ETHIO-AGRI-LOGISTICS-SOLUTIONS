<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyFarmerScoreImproved implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private string $farmerId,
        private string $companyId,
        private int    $newScore,
        private string $scoreBand
    ) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $phone = $this->resolvePhone($this->farmerId);
        if (!$phone) {
            return;
        }

        $bandLabel = ucfirst($this->scoreBand);
        $sms->send($phone, "ምስጋና! የብድር ነጥብዎ ወደ {$this->newScore} ({$bandLabel}) ተሻሽሏል። የጠቅላላ ሐሳቦ ካሉ ያነጋግሩን።");
    }

    private function resolvePhone(string $farmerId): ?string
    {
        return \DB::table('users')->where('id', $farmerId)->value('phone');
    }
}
