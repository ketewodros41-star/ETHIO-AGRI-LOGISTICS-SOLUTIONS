<?php

namespace Fleetbase\TeraHarvest\Events;

use Fleetbase\TeraHarvest\Models\ForwardContract;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractDeposited
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly ForwardContract $contract) {}
}
