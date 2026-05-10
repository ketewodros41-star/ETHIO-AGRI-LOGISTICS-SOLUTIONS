<?php

namespace Fleetbase\TeraHarvest\Events;

use Fleetbase\TeraHarvest\Models\PriceNegotiation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NegotiationAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly PriceNegotiation $negotiation) {}
}
