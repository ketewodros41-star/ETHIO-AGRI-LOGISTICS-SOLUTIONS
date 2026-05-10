<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Services\CarbonTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogShipmentCarbon implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private string $companyId,
        private string $orderId,
        private string $driverId,
        private string $vehicleId,
        private string $vehicleType,
        private float  $distanceKm,
        private float  $cargoWeightKg,
        private string $fuelType = 'diesel'
    ) {}

    public function handle(CarbonTrackingService $service): void
    {
        $service->logShipment(
            $this->companyId,
            $this->orderId,
            $this->driverId,
            $this->vehicleId,
            $this->vehicleType,
            $this->distanceKm,
            $this->cargoWeightKg,
            $this->fuelType
        );
    }
}
