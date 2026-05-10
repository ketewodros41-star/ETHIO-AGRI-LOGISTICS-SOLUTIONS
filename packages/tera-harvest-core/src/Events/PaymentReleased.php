<?php

namespace Fleetbase\TeraHarvest\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PaymentReleased
{
    use Dispatchable;

    public function __construct(
        public readonly string $orderId,
        public readonly string $amountEtb,
        public readonly string $recipientId
    ) {}
}
