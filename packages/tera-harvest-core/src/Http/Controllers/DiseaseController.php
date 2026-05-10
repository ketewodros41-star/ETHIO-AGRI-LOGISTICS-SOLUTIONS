<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\DiseaseReport;
use Fleetbase\TeraHarvest\Models\DiseaseAlert;
use Fleetbase\TeraHarvest\Jobs\DiagnoseCropDisease;
use Fleetbase\TeraHarvest\Jobs\BroadcastDiseaseAlert;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class DiseaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $reports   = DiseaseReport::where('company_id', $companyId)
                                  ->when($request->severity, fn($q) => $q->where('severity', $request->severity))
                                  ->when($request->status, fn($q) => $q->where('status', $request->status))
                                  ->when($request->region_id, fn($q) => $q->where('region_id', $request->region_id))
                                  ->orderByDesc('created_at')
                                  ->paginate(20);

        return response()->json(['data' => $reports]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reported_by' => 'required|string',
            'crop_type'   => 'required|string|max:100',
            'symptoms'    => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = $request->header('X-Company-Id');
        $report    = DiseaseReport::create(array_merge(
            $request->only(['reported_by', 'crop_type', 'disease_name', 'severity', 'symptoms',
                            'photo_urls', 'region_id', 'woreda_id', 'latitude', 'longitude', 'affected_area_ha']),
            ['company_id' => $companyId, 'status' => 'pending']
        ));

        DiagnoseCropDisease::dispatch($report->id);

        return response()->json(['data' => $report, 'message' => 'Report submitted. AI diagnosis initiated.'], 201);
    }

    public function show(string $id): JsonResponse
    {
        $report = DiseaseReport::with('alerts')->findOrFail($id);
        return response()->json(['data' => $report]);
    }

    public function verify(string $id, Request $request): JsonResponse
    {
        $report = DiseaseReport::findOrFail($id);
        $report->update([
            'status'      => 'verified',
            'verified_by' => $request->user()?->id,
            'verified_at' => now(),
        ]);

        if (in_array($report->severity, ['high', 'critical'])) {
            BroadcastDiseaseAlert::dispatch($report->id);
        }

        return response()->json(['data' => $report, 'message' => 'Report verified.']);
    }

    public function alerts(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $alerts    = DiseaseAlert::where('company_id', $companyId)
                                 ->when($request->region_id, fn($q) => $q->where('region_id', $request->region_id))
                                 ->orderByDesc('created_at')
                                 ->paginate(20);

        return response()->json(['data' => $alerts]);
    }
}
