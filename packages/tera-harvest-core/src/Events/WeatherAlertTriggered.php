<?php

namespace Fleetbase\TeraHarvest\Events;

use Fleetbase\TeraHarvest\Models\WeatherAlert;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeatherAlertTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly WeatherAlert $alert) {}
}
