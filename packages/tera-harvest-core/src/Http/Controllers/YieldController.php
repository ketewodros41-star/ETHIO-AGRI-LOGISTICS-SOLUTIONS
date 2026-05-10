<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\FarmPlot;
use Fleetbase\TeraHarvest\Models\YieldPrediction;
use Fleetbase\TeraHarvest\Jobs\GenerateYieldPrediction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class YieldController extends Controller
{
    public function listPlots(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $plots     = FarmPlot::where('company_id', $companyId)
                             ->when($request->farmer_id, fn($q) => $q->where('farmer_id', $request->farmer_id))
                             ->where('is_active', true)
                             ->with('latestPrediction')
                             ->paginate(20);

        return response()->json(['data' => $plots]);
    }

    public function storePlot(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'farmer_id' => 'required|string',
            'plot_name' => 'required|string|max:255',
            'area_ha'   => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plot = FarmPlot::create(array_merge(
            $request->only(['farmer_id', 'plot_name', 'area_ha', 'region_id', 'woreda_id',
                            'latitude', 'longitude', 'polygon_coordinates', 'soil_type',
                            'irrigation_type', 'elevation_m', 'current_crops']),
            ['company_id' => $request->header('X-Company-Id'), 'is_active' => true]
        ));

        return response()->json(['data' => $plot], 201);
    }

    public function showPlot(string $id): JsonResponse
    {
        $plot = FarmPlot::with(['yieldPredictions' => fn($q) => $q->orderByDesc('created_at')->limit(5)])->findOrFail($id);
        return response()->json(['data' => $plot]);
    }

    public function listPredictions(Request $request): JsonResponse
    {
        $companyId   = $request->header('X-Company-Id');
        $predictions = YieldPrediction::where('company_id', $companyId)
                                       ->when($request->farmer_id, fn($q) => $q->where('farmer_id', $request->farmer_id))
                                       ->when($request->crop_type, fn($q) => $q->where('crop_type', $request->crop_type))
                                       ->orderByDesc('created_at')
                                       ->paginate(20);

        return response()->json(['data' => $predictions]);
    }

    public function requestPrediction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'farmer_id'    => 'required|string',
            'farm_plot_id' => 'required|string',
            'crop_type'    => 'required|string|max:100',
            'season'       => 'required|string|max:20',
            'planted_area_ha' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId  = $request->header('X-Company-Id');
        $prediction = YieldPrediction::create(array_merge(
            $request->only(['farmer_id', 'farm_plot_id', 'crop_type', 'season', 'planted_area_ha',
                            'harvest_expected_start', 'harvest_expected_end']),
            ['company_id' => $companyId]
        ));

        GenerateYieldPrediction::dispatch($prediction->id);

        return response()->json(['data' => $prediction, 'message' => 'Prediction generation queued.'], 201);
    }

    public function recordActualYield(string $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actual_yield_kg'    => 'required|numeric|min:0',
            'harvest_actual_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $prediction = YieldPrediction::findOrFail($id);
        $prediction->update([
            'actual_yield_kg'    => $request->actual_yield_kg,
            'harvest_actual_date' => $request->harvest_actual_date,
        ]);
        $prediction->updateAccuracy();

        return response()->json(['data' => $prediction->fresh(), 'message' => 'Actual yield recorded.']);
    }
}
