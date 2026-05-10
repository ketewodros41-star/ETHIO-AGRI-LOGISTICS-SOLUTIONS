<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function supplyByCrop(Request $request): JsonResponse
    {
        $query = HarvestListing::query()
            ->select(
                'crop_type',
                DB::raw('SUM(quantity_kg) as total_kg'),
                DB::raw('COUNT(*) as total_listings'),
                DB::raw('AVG(asking_price_etb) as avg_price_etb')
            )
            ->where('status', 'active')
            ->groupBy('crop_type');

        if ($request->filled('region_id')) {
            $query->whereHas('woreda.zone', fn ($q) => $q->where('region_id', $request->input('region_id')));
        }
        if ($request->filled(['date_from', 'date_to'])) {
            $query->whereBetween('created_at', [$request->input('date_from'), $request->input('date_to')]);
        }

        return response()->json($query->get());
    }

    public function supplyHeatmap(): JsonResponse
    {
        $data = HarvestListing::active()
            ->with('woreda.zone.region')
            ->select('woreda_id', 'crop_type', DB::raw('SUM(quantity_kg) as total_kg'), DB::raw('AVG(asking_price_etb) as avg_price'))
            ->groupBy('woreda_id', 'crop_type')
            ->get();

        return response()->json(['type' => 'FeatureCollection', 'features' => $data]);
    }

    public function priceHistory(Request $request): JsonResponse
    {
        $request->validate([
            'crop_type' => 'required|string',
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after:date_from',
        ]);

        $series = HarvestListing::where('crop_type', $request->input('crop_type'))
            ->whereBetween('created_at', [$request->input('date_from'), $request->input('date_to')])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(asking_price_etb) as avg_price'),
                DB::raw('AVG(ai_suggested_price_etb) as avg_ai_price')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        return response()->json($series);
    }

    public function ordersSummary(Request $request): JsonResponse
    {
        // Placeholder — real data joins Fleetbase orders table
        return response()->json([
            'total_orders'         => 0,
            'completed'            => 0,
            'cancelled'            => 0,
            'avg_fulfilment_hours' => 0,
        ]);
    }

    public function driverPerformance(): JsonResponse
    {
        return response()->json(['message' => 'Joins Fleetbase driver tables — implement per deployment']);
    }

    public function routeBottlenecks(): JsonResponse
    {
        $segments = DB::table('route_segments')
            ->join('route_history', 'route_segments.id', '=', DB::raw('JSON_UNQUOTE(JSON_EXTRACT(route_history.planned_segments, "$[0]"))'))
            ->select(
                'route_segments.id',
                'route_segments.origin_woreda_id',
                'route_segments.destination_woreda_id',
                DB::raw('AVG(route_history.actual_hours - route_history.planned_hours) as avg_delay')
            )
            ->groupBy('route_segments.id', 'route_segments.origin_woreda_id', 'route_segments.destination_woreda_id')
            ->orderByDesc('avg_delay')
            ->limit(20)
            ->get();

        return response()->json($segments);
    }

    public function paymentsSummary(Request $request): JsonResponse
    {
        $query = PaymentTransaction::where('status', 'completed');

        if ($request->filled(['date_from', 'date_to'])) {
            $query->whereBetween('created_at', [$request->input('date_from'), $request->input('date_to')]);
        }

        $summary = $query->select(
            DB::raw('SUM(amount_etb) as total_volume_etb'),
            DB::raw('AVG(amount_etb) as avg_order_value'),
            'provider',
            DB::raw('COUNT(*) as count')
        )->groupBy('provider')->get();

        return response()->json([
            'total_volume_etb' => $query->sum('amount_etb'),
            'by_provider'      => $summary,
        ]);
    }

    public function farmerIncome(Request $request): JsonResponse
    {
        $data = PaymentTransaction::where('type', 'escrow_release')
            ->where('status', 'completed')
            ->with('wallet.owner')
            ->select('wallet_id', DB::raw('SUM(amount_etb) as total_income'))
            ->groupBy('wallet_id')
            ->orderByDesc('total_income')
            ->limit(100)
            ->get();

        return response()->json($data);
    }

    public function exportPdf(Request $request)
    {
        // Delegates to GenerateWeeklyAnalyticsSnapshot job output
        return response()->json(['message' => 'Export queued — use scheduled snapshot endpoint']);
    }

    public function exportExcel(Request $request)
    {
        return response()->json(['message' => 'Excel export — use scheduled snapshot endpoint']);
    }
}
