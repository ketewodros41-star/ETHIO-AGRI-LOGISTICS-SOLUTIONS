<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\GovernmentDataRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class GovernmentApiController extends Controller
{
    public function supplyOverview(Request $request): JsonResponse
    {
        $companyId = $request->attributes->get('gov_company_id');

        $summary = DB::table('harvest_listings')
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->selectRaw('commodity, SUM(quantity_kg) as total_kg, AVG(price_per_kg_etb) as avg_price, COUNT(*) as listings')
            ->groupBy('commodity')
            ->get();

        $this->logRequest($request, '/gov/v1/supply-overview', $summary->count());

        return response()->json([
            'meta'   => ['generated_at' => now()->toIso8601String()],
            'data'   => $summary,
        ]);
    }

    public function farmerStats(Request $request): JsonResponse
    {
        $companyId = $request->attributes->get('gov_company_id');

        $stats = DB::table('farmer_credit_scores')
            ->where('company_id', $companyId)
            ->selectRaw('score_band, COUNT(*) as farmer_count, AVG(total_orders_completed) as avg_orders, AVG(total_kg_traded) as avg_kg')
            ->groupBy('score_band')
            ->get();

        $this->logRequest($request, '/gov/v1/farmer-stats', $stats->count());

        return response()->json([
            'meta' => ['generated_at' => now()->toIso8601String()],
            'data' => $stats,
        ]);
    }

    public function tradeVolume(Request $request): JsonResponse
    {
        $companyId = $request->attributes->get('gov_company_id');

        $volume = DB::table('quality_grades')
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->when($request->from, fn($q) => $q->where('created_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->where('created_at', '<=', $request->to))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, commodity, SUM(net_weight_kg) as total_kg, COUNT(*) as transactions')
            ->groupByRaw('period, commodity')
            ->orderBy('period')
            ->get();

        $this->logRequest($request, '/gov/v1/trade-volume', $volume->count());

        return response()->json([
            'meta' => ['generated_at' => now()->toIso8601String()],
            'data' => $volume,
        ]);
    }

    public function diseaseAlerts(Request $request): JsonResponse
    {
        $companyId = $request->attributes->get('gov_company_id');

        $alerts = DB::table('disease_alerts')
            ->where('company_id', $companyId)
            ->when($request->severity, fn($q) => $q->where('severity', $request->severity))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $this->logRequest($request, '/gov/v1/disease-alerts', $alerts->count());

        return response()->json([
            'meta' => ['generated_at' => now()->toIso8601String()],
            'data' => $alerts,
        ]);
    }

    public function carbonSummary(Request $request): JsonResponse
    {
        $companyId = $request->attributes->get('gov_company_id');

        $summary = DB::table('carbon_credit_reports')
            ->where('company_id', $companyId)
            ->orderByDesc('year')->orderByDesc('month')
            ->limit(12)
            ->get();

        $this->logRequest($request, '/gov/v1/carbon-summary', $summary->count());

        return response()->json([
            'meta' => ['generated_at' => now()->toIso8601String()],
            'data' => $summary,
        ]);
    }

    private function logRequest(Request $request, string $endpoint, int $records): void
    {
        $apiKey = $request->attributes->get('gov_api_key');
        if (!$apiKey) {
            return;
        }

        GovernmentDataRequest::create([
            'api_key_id'       => $apiKey->id,
            'company_id'       => $request->attributes->get('gov_company_id'),
            'endpoint'         => $endpoint,
            'query_params'     => $request->query(),
            'response_code'    => 200,
            'records_returned' => $records,
            'ip_address'       => $request->ip(),
        ]);

        $apiKey->increment('total_requests');
        $apiKey->update(['last_used_at' => now()]);
    }
}
