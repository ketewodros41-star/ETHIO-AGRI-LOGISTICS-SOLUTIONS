<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\WeatherAlert;
use Fleetbase\TeraHarvest\Models\EthiopiaRegion;
use Fleetbase\TeraHarvest\Services\WeatherMonitoringService;
use Fleetbase\TeraHarvest\Jobs\CheckWeatherForecasts;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class WeatherController extends Controller
{
    public function __construct(private WeatherMonitoringService $service) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $alerts    = WeatherAlert::where('company_id', $companyId)
                                 ->active()
                                 ->when($request->region_id, fn($q) => $q->where('region_id', $request->region_id))
                                 ->when($request->severity, fn($q) => $q->where('severity', $request->severity))
                                 ->orderByDesc('created_at')
                                 ->paginate(20);

        return response()->json(['data' => $alerts]);
    }

    public function checkRegion(string $regionId, Request $request): JsonResponse
    {
        $region    = EthiopiaRegion::findOrFail($regionId);
        $companyId = $request->header('X-Company-Id');
        $alerts    = $this->service->checkRegion($region, $companyId);

        return response()->json([
            'data'    => $alerts,
            'message' => count($alerts) . ' alert(s) generated for ' . $region->name_en . '.',
        ]);
    }

    public function impacts(string $alertId): JsonResponse
    {
        $alert   = WeatherAlert::with('shipmentImpacts')->findOrFail($alertId);
        return response()->json(['data' => $alert->shipmentImpacts]);
    }

    public function triggerCheck(Request $request): JsonResponse
    {
        CheckWeatherForecasts::dispatch($request->header('X-Company-Id'));
        return response()->json(['message' => 'Weather forecast check dispatched.']);
    }
}
