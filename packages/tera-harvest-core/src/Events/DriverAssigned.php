<?php

namespace Fleetbase\TeraHarvest\Events;

use Illuminate\Foundation\Events\Dispatchable;

class DriverAssigned
{
    use Dispatchable;

    public function __construct(
        public readonly string $orderId,
        public readonly string $driverId,
        public readonly string $driverName,
        public readonly ?string $farmerId = null
    ) {}
}
