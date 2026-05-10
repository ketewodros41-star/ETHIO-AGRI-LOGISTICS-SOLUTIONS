<?php

namespace Fleetbase\TeraHarvest\Events;

use Fleetbase\TeraHarvest\Models\DiseaseAlert;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiseaseAlertBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly DiseaseAlert $alert) {}
}
