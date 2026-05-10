<?php

namespace Fleetbase\TeraHarvest\Events;

use Fleetbase\TeraHarvest\Models\YieldPrediction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class YieldPredictionReady
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly YieldPrediction $prediction) {}
}
