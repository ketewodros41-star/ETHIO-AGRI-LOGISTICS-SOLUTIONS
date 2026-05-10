<?php

namespace Fleetbase\TeraHarvest\Events;

use Fleetbase\TeraHarvest\Models\AggregationLot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LotSettled
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly AggregationLot $lot) {}
}
