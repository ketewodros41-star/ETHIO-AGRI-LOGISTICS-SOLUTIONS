<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\NgoProgramme;
use Fleetbase\TeraHarvest\Models\NgoBeneficiary;
use Fleetbase\TeraHarvest\Jobs\GenerateNgoProgrammeSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class NgoProgrammeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $programmes = NgoProgramme::where('company_id', $companyId)
                                   ->when($request->status, fn($q) => $q->where('status', $request->status))
                                   ->when($request->focus_area, fn($q) => $q->where('focus_area', $request->focus_area))
                                   ->orderByDesc('created_at')
                                   ->paginate(20);

        return response()->json(['data' => $programmes]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ngo_name'        => 'required|string|max:255',
            'programme_name'  => 'required|string|max:255',
            'focus_area'      => 'required|in:food_security,market_access,climate_resilience,women_empowerment,youth_agri,nutrition,other',
            'start_date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $programme = NgoProgramme::create(array_merge(
            $request->only(['ngo_name', 'programme_name', 'description', 'focus_area', 'region_id',
                            'budget_usd', 'budget_etb', 'target_beneficiaries', 'start_date', 'end_date', 'kpis']),
            [
                'company_id'      => $request->header('X-Company-Id'),
                'programme_code'  => strtoupper(substr(md5($request->programme_name . time()), 0, 10)),
                'status'          => 'planning',
            ]
        ));

        return response()->json(['data' => $programme], 201);
    }

    public function show(string $id): JsonResponse
    {
        $programme = NgoProgramme::with(['beneficiaries', 'impactSnapshots'])->findOrFail($id);
        return response()->json(['data' => $programme]);
    }

    public function enrolBeneficiary(string $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'farmer_id'           => 'required|string',
            'income_baseline_etb' => 'nullable|numeric',
            'yield_baseline_kg'   => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $programme = NgoProgramme::findOrFail($id);
        $companyId = $request->header('X-Company-Id');

        $beneficiary = NgoBeneficiary::firstOrCreate(
            ['programme_id' => $id, 'farmer_id' => $request->farmer_id],
            [
                'company_id'          => $companyId,
                'status'              => 'enrolled',
                'income_baseline_etb' => $request->income_baseline_etb,
                'yield_baseline_kg'   => $request->yield_baseline_kg,
                'baseline_data'       => $request->baseline_data,
                'enrolled_at'         => now(),
            ]
        );

        $programme->increment('enrolled_beneficiaries');

        return response()->json(['data' => $beneficiary], 201);
    }

    public function generateSnapshot(string $id): JsonResponse
    {
        NgoProgramme::findOrFail($id);
        GenerateNgoProgrammeSnapshot::dispatch($id);
        return response()->json(['message' => 'Impact snapshot generation queued.']);
    }

    public function snapshots(string $id): JsonResponse
    {
        $programme = NgoProgramme::findOrFail($id);
        return response()->json(['data' => $programme->impactSnapshots()->paginate(10)]);
    }
}
