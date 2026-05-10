<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\DriverEarningsSummary;
use Fleetbase\TeraHarvest\Models\DriverLeaderboard;
use Fleetbase\TeraHarvest\Jobs\GenerateDriverEarningsSummary;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class DriverEarningsController extends Controller
{
    public function earnings(string $driverId, Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $summaries = DriverEarningsSummary::where('company_id', $companyId)
                                          ->where('driver_id', $driverId)
                                          ->when($request->period_type, fn($q) => $q->where('period_type', $request->period_type))
                                          ->orderByDesc('year')->orderByDesc('week')
                                          ->paginate(20);

        return response()->json(['data' => $summaries]);
    }

    public function leaderboard(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'period_type'  => 'required|in:weekly,monthly',
            'period_label' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = $request->header('X-Company-Id');
        $entries   = DriverLeaderboard::where('company_id', $companyId)
                                       ->where('period_type', $request->period_type)
                                       ->where('period_label', $request->period_label)
                                       ->when($request->region_id, fn($q) => $q->where('region_id', $request->region_id))
                                       ->orderBy('rank')
                                       ->get()
                                       ->map(function ($entry) {
                                           // Anonymise driver identity beyond top 3
                                           if ($entry->rank > 3) {
                                               $entry->driver_id    = null;
                                               $entry->display_name = "Driver #{$entry->rank}";
                                           }
                                           return $entry;
                                       });

        return response()->json(['data' => $entries]);
    }

    public function generateSummary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'driver_id'    => 'required|string',
            'period_type'  => 'required|in:weekly,monthly',
            'period_label' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        GenerateDriverEarningsSummary::dispatch(
            $request->driver_id,
            $request->header('X-Company-Id'),
            $request->period_type,
            $request->period_label
        );

        return response()->json(['message' => 'Earnings summary generation queued.']);
    }

    public function pendingDisbursements(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $pending   = DriverEarningsSummary::where('company_id', $companyId)
                                           ->where('disbursed', false)
                                           ->where('net_earnings_etb', '>', 0)
                                           ->get();

        $total = $pending->sum(fn($s) => (float) $s->net_earnings_etb);

        return response()->json([
            'data'          => $pending,
            'total_etb'     => round($total, 2),
            'driver_count'  => $pending->count(),
        ]);
    }
}
