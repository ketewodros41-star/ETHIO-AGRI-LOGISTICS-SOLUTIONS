<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\YieldPrediction;
use Fleetbase\TeraHarvest\Models\FarmPlot;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateYieldPrediction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(private string $predictionId) {}

    public function handle(TeraHarvestAIClient $ai): void
    {
        $prediction = YieldPrediction::find($this->predictionId);
        if (!$prediction) {
            return;
        }

        $plot = $prediction->farm_plot_id ? FarmPlot::find($prediction->farm_plot_id) : null;

        try {
            $result = $ai->predictYield([
                'crop_type'        => $prediction->crop_type,
                'season'           => $prediction->season,
                'planted_area_ha'  => $prediction->planted_area_ha,
                'soil_type'        => $plot?->soil_type,
                'irrigation_type'  => $plot?->irrigation_type,
                'elevation_m'      => $plot?->elevation_m,
                'region_id'        => $plot?->region_id,
                'farmer_id'        => $prediction->farmer_id,
            ]);

            $prediction->update([
                'predicted_yield_kg'     => $result['predicted_yield_kg'] ?? null,
                'predicted_yield_min_kg' => $result['min_yield_kg'] ?? null,
                'predicted_yield_max_kg' => $result['max_yield_kg'] ?? null,
                'ai_reasoning'           => $result,
                'ai_confidence'          => $result['confidence'] ?? null,
                'harvest_expected_start' => $result['harvest_start'] ?? null,
                'harvest_expected_end'   => $result['harvest_end'] ?? null,
                'model_inputs'           => [
                    'soil_type'       => $plot?->soil_type,
                    'irrigation_type' => $plot?->irrigation_type,
                    'elevation_m'     => $plot?->elevation_m,
                ],
            ]);

            Log::info("YieldPrediction {$this->predictionId} generated: {$result['predicted_yield_kg']} kg");
        } catch (\Exception $e) {
            Log::error("GenerateYieldPrediction failed for {$this->predictionId}: " . $e->getMessage());
            throw $e;
        }
    }
}
