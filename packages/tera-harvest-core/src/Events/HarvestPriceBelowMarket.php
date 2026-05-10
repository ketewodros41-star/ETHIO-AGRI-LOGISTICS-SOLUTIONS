<?php

namespace Fleetbase\TeraHarvest\Events;

use Illuminate\Foundation\Events\Dispatchable;

class HarvestPriceBelowMarket
{
    use Dispatchable;

    public function __construct(
        public readonly string $listingId,
        public readonly string $farmerId,
        public readonly string $askingPrice,
        public readonly string $ecxPrice
    ) {}
}
