<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class WeatherAlert extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'weather_alerts';

    protected $fillable = [
        'company_id', 'alert_type', 'severity', 'region_id', 'description',
        'max_wind_speed_kmh', 'precipitation_mm', 'min_temp_c', 'max_temp_c',
        'raw_forecast', 'shipments_at_risk', 'shipments_rerouted',
        'farmers_notified', 'forecast_valid_from', 'forecast_valid_until', 'is_active',
    ];

    protected $casts = [
        'max_wind_speed_kmh'    => 'decimal:2',
        'precipitation_mm'      => 'decimal:2',
        'min_temp_c'            => 'decimal:2',
        'max_temp_c'            => 'decimal:2',
        'raw_forecast'          => 'array',
        'shipments_at_risk'     => 'integer',
        'shipments_rerouted'    => 'integer',
        'farmers_notified'      => 'integer',
        'is_active'             => 'boolean',
        'forecast_valid_from'   => 'datetime',
        'forecast_valid_until'  => 'datetime',
    ];

    public function shipmentImpacts()
    {
        return $this->hasMany(ShipmentWeatherImpact::class, 'alert_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
