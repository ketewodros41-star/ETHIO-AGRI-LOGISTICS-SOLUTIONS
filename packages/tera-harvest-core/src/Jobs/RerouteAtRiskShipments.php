<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\WeatherAlert;
use Fleetbase\TeraHarvest\Models\ShipmentWeatherImpact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RerouteAtRiskShipments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $alertId, private string $companyId) {}

    public function handle(): void
    {
        $alert = WeatherAlert::find($this->alertId);
        if (!$alert) {
            return;
        }

        $activeOrders = DB::table('orders')
                          ->where('company_id', $this->companyId)
                          ->whereIn('status', ['dispatched', 'in_progress'])
                          ->get();

        $rerouteCount = 0;
        foreach ($activeOrders as $order) {
            ShipmentWeatherImpact::create([
                'alert_id'    => $this->alertId,
                'order_id'    => $order->id,
                'company_id'  => $this->companyId,
                'action_taken' => 'delayed',
                'delay_hours'  => $alert->severity === 'emergency' ? 24 : 6,
                'notes'        => "Auto-delayed due to {$alert->alert_type} alert (severity: {$alert->severity})",
                'actioned_at'  => now(),
            ]);
            $rerouteCount++;
        }

        $alert->update(['shipments_at_risk' => $rerouteCount, 'shipments_rerouted' => $rerouteCount]);
        Log::info("RerouteAtRiskShipments: {$rerouteCount} shipment(s) flagged for alert {$this->alertId}");
    }
}
