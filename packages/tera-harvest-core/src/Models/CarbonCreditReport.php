<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class CarbonCreditReport extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'carbon_credit_reports';

    protected $fillable = [
        'company_id', 'report_period', 'year', 'month',
        'total_co2e_kg', 'total_distance_km', 'total_orders',
        'co2e_per_order_kg', 'co2e_per_km_kg', 'industry_benchmark_kg', 'variance_pct',
        'breakdown_by_fuel_type', 'breakdown_by_region',
        'report_url', 'document_hash', 'generated_at',
    ];

    protected $casts = [
        'year'                    => 'integer',
        'month'                   => 'integer',
        'total_co2e_kg'           => 'decimal:3',
        'total_distance_km'       => 'decimal:3',
        'total_orders'            => 'integer',
        'co2e_per_order_kg'       => 'decimal:3',
        'co2e_per_km_kg'          => 'decimal:5',
        'industry_benchmark_kg'   => 'decimal:3',
        'variance_pct'            => 'decimal:2',
        'breakdown_by_fuel_type'  => 'array',
        'breakdown_by_region'     => 'array',
        'generated_at'            => 'datetime',
    ];

    public function isBelowBenchmark(): bool
    {
        if ($this->industry_benchmark_kg === null) {
            return false;
        }
        return bccomp((string) $this->total_co2e_kg, (string) $this->industry_benchmark_kg, 3) < 0;
    }
}
