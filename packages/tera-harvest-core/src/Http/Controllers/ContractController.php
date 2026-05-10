<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\ForwardContract;
use Fleetbase\TeraHarvest\Models\BuyerSubscription;
use Fleetbase\TeraHarvest\Services\ForwardContractService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
    public function __construct(private ForwardContractService $service) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $contracts = ForwardContract::where('company_id', $companyId)
                                    ->when($request->status, fn($q) => $q->where('status', $request->status))
                                    ->when($request->buyer_id, fn($q) => $q->where('buyer_id', $request->buyer_id))
                                    ->orderByDesc('created_at')
                                    ->paginate(20);

        return response()->json(['data' => $contracts]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'buyer_id'             => 'required|string',
            'commodity'            => 'required|string|max:100',
            'quantity_kg'          => 'required|numeric|min:1',
            'price_per_kg_etb'     => 'required|numeric|min:0',
            'delivery_start_date'  => 'required|date',
            'delivery_end_date'    => 'required|date|after:delivery_start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contract = $this->service->create(array_merge(
            $request->only(['buyer_id', 'commodity', 'quantity_kg', 'price_per_kg_etb',
                            'quality_grade', 'delivery_region_id', 'delivery_start_date', 'delivery_end_date', 'deposit_pct']),
            ['company_id' => $request->header('X-Company-Id')]
        ));

        return response()->json(['data' => $contract], 201);
    }

    public function show(string $id): JsonResponse
    {
        $contract = ForwardContract::with('fulfilments')->findOrFail($id);
        return response()->json(['data' => $contract]);
    }

    public function payDeposit(string $id, Request $request): JsonResponse
    {
        $contract = ForwardContract::findOrFail($id);

        try {
            $this->service->processDeposit($contract, $request->transaction_id ?? uniqid('dep_'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $contract->fresh(), 'message' => 'Deposit processed.']);
    }

    public function cancel(string $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason'         => 'required|string',
            'refund_deposit' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contract = ForwardContract::findOrFail($id);
        try {
            $this->service->cancel($contract, $request->reason, (bool) $request->refund_deposit);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $contract->fresh(), 'message' => 'Contract cancelled.']);
    }

    // Buyer subscriptions
    public function listSubscriptions(Request $request): JsonResponse
    {
        $companyId     = $request->header('X-Company-Id');
        $subscriptions = BuyerSubscription::where('company_id', $companyId)
                                          ->when($request->buyer_id, fn($q) => $q->where('buyer_id', $request->buyer_id))
                                          ->paginate(20);

        return response()->json(['data' => $subscriptions]);
    }

    public function storeSubscription(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'buyer_id'    => 'required|string',
            'commodity'   => 'required|string|max:100',
            'frequency'   => 'required|in:daily,weekly,fortnightly,monthly',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $subscription = BuyerSubscription::create(array_merge(
            $request->only(['buyer_id', 'commodity', 'min_quantity_kg', 'max_price_per_kg_etb',
                            'quality_grade', 'preferred_region_id', 'frequency']),
            ['company_id' => $request->header('X-Company-Id'), 'is_active' => true]
        ));

        return response()->json(['data' => $subscription], 201);
    }
}
