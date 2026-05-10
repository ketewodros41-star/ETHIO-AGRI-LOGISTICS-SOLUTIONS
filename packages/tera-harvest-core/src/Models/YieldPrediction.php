<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class YieldPrediction extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'yield_predictions';

    protected $fillable = [
        'farmer_id', 'company_id', 'farm_plot_id', 'crop_type', 'season',
        'planted_area_ha', 'predicted_yield_kg', 'predicted_yield_min_kg', 'predicted_yield_max_kg',
        'actual_yield_kg', 'accuracy_pct', 'model_inputs', 'ai_reasoning', 'ai_confidence',
        'harvest_expected_start', 'harvest_expected_end', 'harvest_actual_date',
    ];

    protected $casts = [
        'planted_area_ha'         => 'decimal:4',
        'predicted_yield_kg'      => 'decimal:3',
        'predicted_yield_min_kg'  => 'decimal:3',
        'predicted_yield_max_kg'  => 'decimal:3',
        'actual_yield_kg'         => 'decimal:3',
        'accuracy_pct'            => 'decimal:2',
        'ai_confidence'           => 'decimal:2',
        'model_inputs'            => 'array',
        'ai_reasoning'            => 'array',
        'harvest_expected_start'  => 'date',
        'harvest_expected_end'    => 'date',
        'harvest_actual_date'     => 'date',
    ];

    public function plot()
    {
        return $this->belongsTo(FarmPlot::class, 'farm_plot_id');
    }

    public function updateAccuracy(): void
    {
        if ($this->actual_yield_kg === null || $this->predicted_yield_kg === null) {
            return;
        }
        $error = abs(
            (float) bcsub((string) $this->predicted_yield_kg, (string) $this->actual_yield_kg, 3)
        );
        $accuracy = (float) bcsub('100', (string) bcdiv(
            bcmul((string) $error, '100', 2),
            (string) $this->actual_yield_kg,
            2
        ), 2);
        $this->accuracy_pct = max(0, $accuracy);
        $this->save();
    }
}
