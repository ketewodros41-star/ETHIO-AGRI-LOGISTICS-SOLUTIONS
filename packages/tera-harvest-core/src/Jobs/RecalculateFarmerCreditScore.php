<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Services\FarmerCreditScoringService;
use Fleetbase\TeraHarvest\Models\FarmerCreditScore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateFarmerCreditScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private string $farmerId,
        private string $companyId,
        private string $triggeredByEvent = 'system'
    ) {}

    public function handle(FarmerCreditScoringService $service): void
    {
        $score = $service->recalculate($this->farmerId, $this->companyId, $this->triggeredByEvent);
        Log::info("Credit score recalculated for farmer {$this->farmerId}: {$score->score} ({$score->score_band})");

        if ($score->getOriginal('score') !== null && $score->score > $score->getOriginal('score')) {
            NotifyFarmerScoreImproved::dispatch($this->farmerId, $this->companyId, $score->score, $score->score_band);
        }
    }
}
