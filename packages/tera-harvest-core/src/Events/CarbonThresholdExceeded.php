<?php

namespace Fleetbase\TeraHarvest\Events;

use Fleetbase\TeraHarvest\Models\CarbonCreditReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CarbonThresholdExceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CarbonCreditReport $report,
        public readonly string $benchmark
    ) {}
}
