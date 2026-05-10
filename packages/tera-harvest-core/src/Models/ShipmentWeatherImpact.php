<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class ShipmentWeatherImpact extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'shipment_weather_impacts';

    protected $fillable = [
        'alert_id', 'order_id', 'company_id', 'action_taken',
        'original_route_id', 'new_route_id', 'delay_hours', 'notes', 'actioned_at',
    ];

    protected $casts = [
        'delay_hours'  => 'integer',
        'actioned_at'  => 'datetime',
    ];

    public function alert()
    {
        return $this->belongsTo(WeatherAlert::class, 'alert_id');
    }
}
