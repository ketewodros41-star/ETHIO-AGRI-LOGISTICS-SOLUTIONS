<?php

namespace Fleetbase\TeraHarvest\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ColdChainAlert
{
    use Dispatchable;

    public function __construct(
        public readonly string $shipmentId,
        public readonly float $actualTemp,
        public readonly float $maxTemp
    ) {}
}
