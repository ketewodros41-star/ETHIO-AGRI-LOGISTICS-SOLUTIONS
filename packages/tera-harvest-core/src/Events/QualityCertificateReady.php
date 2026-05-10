<?php

namespace Fleetbase\TeraHarvest\Events;

use Fleetbase\TeraHarvest\Models\QualityGrade;
use Illuminate\Foundation\Events\Dispatchable;

class QualityCertificateReady
{
    use Dispatchable;

    public function __construct(public readonly QualityGrade $grade) {}
}
