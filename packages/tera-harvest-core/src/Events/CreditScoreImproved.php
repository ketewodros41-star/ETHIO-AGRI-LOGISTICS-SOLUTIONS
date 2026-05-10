<?php

namespace Fleetbase\TeraHarvest\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreditScoreImproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $farmerId,
        public readonly string $companyId,
        public readonly int    $oldScore,
        public readonly int    $newScore,
        public readonly string $scoreBand
    ) {}
}
