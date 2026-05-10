<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Jobs\GenerateQualityCertificate;
use Fleetbase\TeraHarvest\Models\ColdChainLog;
use Fleetbase\TeraHarvest\Models\QualityGrade;
use Fleetbase\TeraHarvest\Jobs\CheckColdChainThreshold;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QualityController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_id'        => 'nullable|string',
            'listing_id'         => 'nullable|string',
            'crop_type'          => 'required|string',
            'grading_standard'   => 'required|in:ecx,fao,custom',
            'overall_grade'      => 'required|in:A,B,C,rejected',
            'weight_kg'          => 'required|numeric|min:0.001',
            'moisture_pct'       => 'nullable|numeric|between:0,100',
            'foreign_matter_pct' => 'nullable|numeric|between:0,100',
            'defect_pct'         => 'nullable|numeric|between:0,100',
            'colour_score'       => 'nullable|integer|between:1,5',
            'smell_score'        => 'nullable|integer|between:1,5',
            'custom_attributes'  => 'nullable|array',
            'notes'              => 'nullable|string|max:2000',
        ]);

        $grade = QualityGrade::create(array_merge($validated, [
            'uuid'               => (string) Str::uuid(),
            'inspector_id'       => auth()->id(),
            'certificate_number' => QualityGrade::generateCertificateNumber(),
            'status'             => 'draft',
            'graded_at'          => now(),
            'created_by'         => auth()->id(),
        ]));

        GenerateQualityCertificate::dispatch($grade->id);

        return response()->json($grade, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(QualityGrade::with('photos')->findOrFail($id));
    }

    public function certificate(string $id)
    {
        $grade = QualityGrade::findOrFail($id);

        if (!$grade->certificate_pdf_url) {
            return response()->json(['error' => 'Certificate not yet generated.'], 202);
        }

        return redirect($grade->certificate_pdf_url);
    }

    public function dispute(Request $request, string $id): JsonResponse
    {
        $grade = QualityGrade::where('status', 'issued')->findOrFail($id);
        $grade->update(['status' => 'disputed']);
        return response()->json($grade);
    }

    public function coldChainLog(string $shipmentId): JsonResponse
    {
        return response()->json(
            ColdChainLog::where('shipment_id', $shipmentId)->orderBy('recorded_at')->get()
        );
    }

    public function sensorReading(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipment_id'         => 'required|string',
            'sensor_id'           => 'required|string',
            'temperature_celsius' => 'required|numeric|between:-100,100',
            'humidity_pct'        => 'nullable|numeric|between:0,100',
            'lat'                 => 'nullable|numeric',
            'lng'                 => 'nullable|numeric',
            'recorded_at'         => 'nullable|date',
        ]);

        $log = ColdChainLog::create(array_merge($validated, [
            'uuid'        => (string) Str::uuid(),
            'recorded_at' => $validated['recorded_at'] ?? now(),
            'created_at'  => now(),
        ]));

        CheckColdChainThreshold::dispatch($log->id);

        return response()->json($log, 201);
    }
}
