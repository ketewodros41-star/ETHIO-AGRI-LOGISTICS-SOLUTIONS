<?php

namespace Fleetbase\TeraHarvest\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DeliveryConfirmed
{
    use Dispatchable;

    public function __construct(
        public readonly string $orderId,
        public readonly ?string $farmerId = null
    ) {}
}
