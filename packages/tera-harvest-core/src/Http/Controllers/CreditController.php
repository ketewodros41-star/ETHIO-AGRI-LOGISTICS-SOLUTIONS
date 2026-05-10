<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\FarmerCreditScore;
use Fleetbase\TeraHarvest\Models\MicrofinanceCreditRequest;
use Fleetbase\TeraHarvest\Services\FarmerCreditScoringService;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class CreditController extends Controller
{
    public function __construct(
        private FarmerCreditScoringService $scoring,
        private TeraHarvestAIClient $ai
    ) {}

    public function show(string $farmerId, Request $request): JsonResponse
    {
        $score = FarmerCreditScore::where('farmer_id', $farmerId)
                                  ->where('company_id', $request->header('X-Company-Id'))
                                  ->first();

        if (!$score) {
            return response()->json(['message' => 'No credit score found.'], 404);
        }

        return response()->json(['data' => $score]);
    }

    public function history(string $farmerId, Request $request): JsonResponse
    {
        $score = FarmerCreditScore::where('farmer_id', $farmerId)
                                  ->where('company_id', $request->header('X-Company-Id'))
                                  ->first();

        if (!$score) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $score->history()->paginate(20)]);
    }

    public function recalculate(string $farmerId, Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $score     = $this->scoring->recalculate($farmerId, $companyId, 'manual_recalculate');

        return response()->json(['data' => $score, 'message' => 'Credit score recalculated.']);
    }

    public function explain(string $farmerId, Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $score     = FarmerCreditScore::where('farmer_id', $farmerId)
                                      ->where('company_id', $companyId)
                                      ->first();

        if (!$score) {
            return response()->json(['message' => 'No credit score found.'], 404);
        }

        $explanation = $this->ai->explainCreditScore([
            'farmer_id'  => $farmerId,
            'score'      => $score->toArray(),
        ]);

        return response()->json(['data' => $explanation]);
    }

    public function microfinancePackage(string $farmerId, Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $score     = FarmerCreditScore::where('farmer_id', $farmerId)
                                      ->where('company_id', $companyId)
                                      ->first();

        if (!$score || !$score->isEligibleForMicrofinance()) {
            return response()->json(['message' => 'Farmer is not eligible for microfinance.'], 422);
        }

        return response()->json([
            'data' => $this->scoring->buildAnonymisedPackage($score),
        ]);
    }

    public function storeCreditRequest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'farmer_id'            => 'required|string',
            'partner_name'         => 'required|string|max:255',
            'partner_reference'    => 'nullable|string',
            'requested_amount_etb' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = $request->header('X-Company-Id');
        $score     = FarmerCreditScore::where('farmer_id', $request->farmer_id)
                                      ->where('company_id', $companyId)
                                      ->first();

        $creditRequest = MicrofinanceCreditRequest::create([
            'farmer_id'              => $request->farmer_id,
            'company_id'             => $companyId,
            'partner_name'           => $request->partner_name,
            'partner_reference'      => $request->partner_reference,
            'requested_amount_etb'   => $request->requested_amount_etb,
            'score_at_request'       => $score?->score ?? 0,
            'score_band_at_request'  => $score?->score_band ?? 'unrated',
            'data_package'           => $score ? $this->scoring->buildAnonymisedPackage($score) : null,
            'status'                 => 'pending',
        ]);

        return response()->json(['data' => $creditRequest], 201);
    }

    public function updateCreditRequestStatus(string $id, Request $request): JsonResponse
    {
        $creditRequest = MicrofinanceCreditRequest::findOrFail($id);
        $validator     = Validator::make($request->all(), [
            'status'               => 'required|in:approved,declined,disbursed,repaid,defaulted',
            'approved_amount_etb'  => 'nullable|numeric',
            'interest_rate_pct'    => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updates = ['status' => $request->status];
        if ($request->status === 'approved') {
            $updates['approved_amount_etb'] = $request->approved_amount_etb;
            $updates['interest_rate_pct']   = $request->interest_rate_pct;
        }
        if ($request->status === 'disbursed') {
            $updates['disbursed_at'] = now();
        }
        if ($request->status === 'repaid') {
            $updates['repaid_at'] = now();
        }

        $creditRequest->update($updates);
        return response()->json(['data' => $creditRequest]);
    }
}
