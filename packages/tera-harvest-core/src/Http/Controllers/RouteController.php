<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Jobs\UpdateRouteConditionScore;
use Fleetbase\TeraHarvest\Models\RouteHistory;
use Fleetbase\TeraHarvest\Models\RouteReport;
use Fleetbase\TeraHarvest\Models\RouteSegment;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class RouteController extends Controller
{
    public function __construct(private readonly TeraHarvestAIClient $ai) {}

    public function segments(Request $request): JsonResponse
    {
        $query = RouteSegment::with(['origin', 'destination']);

        if ($request->filled('origin_woreda')) {
            $query->where('origin_woreda_id', $request->input('origin_woreda'));
        }
        if ($request->filled('dest_woreda')) {
            $query->where('destination_woreda_id', $request->input('dest_woreda'));
        }

        return response()->json($query->get());
    }

    public function recommend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'origin_woreda_id'      => 'required|string|exists:ethiopia_woredas,id',
            'destination_woreda_id' => 'required|string|exists:ethiopia_woredas,id',
            'vehicle_type'          => 'nullable|string',
            'date'                  => 'nullable|date',
            'priority'              => 'nullable|in:speed,fuel,reliability',
        ]);

        $result = $this->ai->dispatchRecommendation('route-query', [], []);

        return response()->json($result);
    }

    public function storeReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'segment_id'  => 'required|string|exists:route_segments,id',
            'report_type' => 'required|in:road_condition,hazard,closure,delay,new_route,congestion',
            'description' => 'nullable|string|max:1000',
            'lat'         => 'nullable|numeric',
            'lng'         => 'nullable|numeric',
            'severity'    => 'required|in:low,medium,high,impassable',
            'photo_url'   => 'nullable|url',
            'valid_from'  => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
        ]);

        $report = RouteReport::create(array_merge($validated, [
            'uuid'        => (string) Str::uuid(),
            'reported_by' => auth()->id(),
        ]));

        UpdateRouteConditionScore::dispatch($validated['segment_id']);

        return response()->json($report, 201);
    }

    public function reports(Request $request): JsonResponse
    {
        $query = RouteReport::with('segment');

        if ($request->filled('segment_id')) {
            $query->where('segment_id', $request->input('segment_id'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        return response()->json($query->latest()->get());
    }

    public function storeHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'         => 'required|string',
            'planned_segments' => 'nullable|array',
            'actual_path'      => 'nullable|array',
            'planned_hours'    => 'nullable|numeric',
            'actual_hours'     => 'nullable|numeric',
            'fuel_litres_used' => 'nullable|numeric',
            'incidents'        => 'nullable|array',
        ]);

        $history = RouteHistory::create(array_merge($validated, [
            'driver_id'  => auth()->id(),
            'created_at' => now(),
        ]));

        return response()->json($history, 201);
    }
}
