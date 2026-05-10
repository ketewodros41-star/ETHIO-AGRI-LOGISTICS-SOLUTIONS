<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\AggregationLot;
use Fleetbase\TeraHarvest\Services\BulkAggregationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AggregationController extends Controller
{
    public function __construct(private BulkAggregationService $service) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $lots      = AggregationLot::where('company_id', $companyId)
                                   ->when($request->status, fn($q) => $q->where('status', $request->status))
                                   ->when($request->commodity, fn($q) => $q->where('commodity', $request->commodity))
                                   ->orderByDesc('created_at')
                                   ->paginate(20);

        return response()->json(['data' => $lots]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'commodity'  => 'required|string|max:100',
            'target_kg'  => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = $request->header('X-Company-Id');
        $lot       = AggregationLot::create([
            'company_id'           => $companyId,
            'lot_number'           => 'LOT-' . strtoupper(Str::random(8)),
            'commodity'            => $request->commodity,
            'collection_point_id'  => $request->collection_point_id,
            'target_kg'            => $request->target_kg,
            'status'               => 'open',
        ]);

        return response()->json(['data' => $lot], 201);
    }

    public function show(string $id): JsonResponse
    {
        $lot = AggregationLot::with('contributions')->findOrFail($id);
        return response()->json(['data' => $lot]);
    }

    public function addContribution(string $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'farmer_id'      => 'required|string',
            'contributed_kg' => 'required|numeric|min:0.001',
            'quality_grade'  => 'required|in:A,B,C',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lot          = AggregationLot::findOrFail($id);
        $companyId    = $request->header('X-Company-Id');

        try {
            $contribution = $this->service->addContribution(
                $lot,
                $request->farmer_id,
                $companyId,
                (string) $request->contributed_kg,
                $request->quality_grade
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $contribution], 201);
    }

    public function settle(string $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'total_sale_etb' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lot = AggregationLot::findOrFail($id);
        $lot->update(['status' => 'sold', 'sold_at' => now(), 'buyer_id' => $request->buyer_id]);

        try {
            $this->service->settleLot($lot, (string) $request->total_sale_etb);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Lot settled and payouts disbursed.', 'data' => $lot->fresh()]);
    }
}
