<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class CarbonEmissionsLog extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'carbon_emissions_log';

    public $timestamps = false;

    protected $fillable = [
        'company_id', 'order_id', 'driver_id', 'vehicle_id', 'activity_type',
        'fuel_type', 'distance_km', 'fuel_consumed_litres', 'cargo_weight_kg',
        'emission_factor_kg_co2_per_km', 'co2_kg', 'co2e_kg',
        'ipcc_source_version', 'activity_date',
    ];

    protected $casts = [
        'distance_km'                   => 'decimal:3',
        'fuel_consumed_litres'          => 'decimal:3',
        'cargo_weight_kg'               => 'decimal:3',
        'emission_factor_kg_co2_per_km' => 'decimal:5',
        'co2_kg'                        => 'decimal:3',
        'co2e_kg'                       => 'decimal:3',
        'activity_date'                 => 'datetime',
        'created_at'                    => 'datetime',
    ];
}
