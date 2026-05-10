<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FarmPlot extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'farm_plots';

    protected $fillable = [
        'farmer_id', 'company_id', 'plot_name', 'area_ha',
        'region_id', 'woreda_id', 'latitude', 'longitude', 'polygon_coordinates',
        'soil_type', 'irrigation_type', 'elevation_m',
        'current_crops', 'crop_history', 'is_active',
    ];

    protected $casts = [
        'area_ha'              => 'decimal:4',
        'latitude'             => 'decimal:7',
        'longitude'            => 'decimal:7',
        'elevation_m'          => 'decimal:2',
        'polygon_coordinates'  => 'array',
        'current_crops'        => 'array',
        'crop_history'         => 'array',
        'is_active'            => 'boolean',
    ];

    public function yieldPredictions()
    {
        return $this->hasMany(YieldPrediction::class, 'farm_plot_id');
    }

    public function latestPrediction()
    {
        return $this->hasOne(YieldPrediction::class, 'farm_plot_id')->latestOfMany('created_at');
    }
}
