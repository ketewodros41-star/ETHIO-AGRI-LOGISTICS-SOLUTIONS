<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\CarbonCreditReport;
use Fleetbase\TeraHarvest\Services\CarbonTrackingService;
use Fleetbase\TeraHarvest\Jobs\GenerateMonthlyCarbonReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class CarbonController extends Controller
{
    public function __construct(private CarbonTrackingService $service) {}

    public function reports(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $reports   = CarbonCreditReport::where('company_id', $companyId)
                                        ->orderByDesc('year')->orderByDesc('month')
                                        ->paginate(12);

        return response()->json(['data' => $reports]);
    }

    public function generateReport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year'  => 'required|integer|min:2020',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = $request->header('X-Company-Id');
        $report    = $this->service->generateMonthlyReport($companyId, $request->year, $request->month);

        return response()->json(['data' => $report]);
    }

    public function logEmission(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id'     => 'required|string',
            'driver_id'    => 'required|string',
            'vehicle_id'   => 'required|string',
            'vehicle_type' => 'required|string',
            'distance_km'  => 'required|numeric|min:0',
            'cargo_weight_kg' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = $request->header('X-Company-Id');
        $log       = $this->service->logShipment(
            $companyId,
            $request->order_id,
            $request->driver_id,
            $request->vehicle_id,
            $request->vehicle_type,
            (float) $request->distance_km,
            (float) $request->cargo_weight_kg,
            $request->fuel_type ?? 'diesel'
        );

        return response()->json(['data' => $log], 201);
    }

    public function summary(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $year      = $request->year ?? now()->year;

        $reports = CarbonCreditReport::where('company_id', $companyId)
                                      ->where('year', $year)
                                      ->orderBy('month')
                                      ->get();

        $totalCo2e = $reports->sum(fn($r) => (float) $r->total_co2e_kg);

        return response()->json([
            'data' => [
                'year'         => $year,
                'reports'      => $reports,
                'total_co2e_kg' => round($totalCo2e, 3),
            ],
        ]);
    }
}
