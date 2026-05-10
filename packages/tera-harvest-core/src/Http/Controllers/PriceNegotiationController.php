<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\PriceNegotiation;
use Fleetbase\TeraHarvest\Models\NegotiationTurn;
use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Fleetbase\TeraHarvest\Jobs\CreateOrderFromAcceptedNegotiation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class PriceNegotiationController extends Controller
{
    public function __construct(private TeraHarvestAIClient $ai) {}

    public function index(Request $request): JsonResponse
    {
        $companyId    = $request->header('X-Company-Id');
        $negotiations = PriceNegotiation::where('company_id', $companyId)
                                         ->when($request->status, fn($q) => $q->where('status', $request->status))
                                         ->when($request->buyer_id, fn($q) => $q->where('buyer_id', $request->buyer_id))
                                         ->orderByDesc('created_at')
                                         ->paginate(20);

        return response()->json(['data' => $negotiations]);
    }

    public function initiate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id'  => 'required|string',
            'buyer_id'    => 'required|string',
            'offer_etb'   => 'required|numeric|min:0',
            'quantity_kg' => 'required|numeric|min:0.001',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $listing    = HarvestListing::findOrFail($request->listing_id);
        $companyId  = $request->header('X-Company-Id');

        $existing = PriceNegotiation::where('listing_id', $request->listing_id)
                                     ->where('buyer_id', $request->buyer_id)
                                     ->where('status', 'active')
                                     ->first();

        if ($existing) {
            return response()->json(['message' => 'An active negotiation already exists.', 'data' => $existing], 409);
        }

        $negotiation = PriceNegotiation::create([
            'company_id'        => $companyId,
            'listing_id'        => $request->listing_id,
            'buyer_id'          => $request->buyer_id,
            'seller_id'         => $listing->farmer_id,
            'initial_ask_etb'   => $listing->price_per_kg_etb,
            'current_offer_etb' => $request->offer_etb,
            'quantity_kg'       => $request->quantity_kg,
            'status'            => 'active',
            'expires_at'        => now()->addHours(48),
        ]);

        NegotiationTurn::create([
            'negotiation_id'    => $negotiation->id,
            'company_id'        => $companyId,
            'turn_number'       => 1,
            'actor'             => 'buyer',
            'offered_price_etb' => $request->offer_etb,
            'message'           => $request->message,
        ]);

        $negotiation->update(['current_turn' => 1]);

        return response()->json(['data' => $negotiation->load('turns')], 201);
    }

    public function respond(string $id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'actor'           => 'required|in:buyer,seller',
            'offered_price_etb' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $negotiation = PriceNegotiation::findOrFail($id);
        $companyId   = $request->header('X-Company-Id');

        if ($negotiation->status !== 'active') {
            return response()->json(['message' => 'Negotiation is no longer active.'], 422);
        }
        if ($negotiation->isExpired()) {
            $negotiation->update(['status' => 'expired']);
            return response()->json(['message' => 'Negotiation has expired.'], 422);
        }
        if ($negotiation->hasReachedMaxTurns()) {
            return response()->json(['message' => 'Maximum negotiation turns reached.'], 422);
        }

        $newTurn = $negotiation->current_turn + 1;
        NegotiationTurn::create([
            'negotiation_id'    => $id,
            'company_id'        => $companyId,
            'turn_number'       => $newTurn,
            'actor'             => $request->actor,
            'offered_price_etb' => $request->offered_price_etb,
            'message'           => $request->message,
        ]);

        $negotiation->update([
            'current_offer_etb' => $request->offered_price_etb,
            'current_turn'      => $newTurn,
        ]);

        return response()->json(['data' => $negotiation->fresh()->load('turns')]);
    }

    public function accept(string $id, Request $request): JsonResponse
    {
        $negotiation = PriceNegotiation::findOrFail($id);

        if ($negotiation->status !== 'active') {
            return response()->json(['message' => 'Negotiation is not active.'], 422);
        }

        $negotiation->update([
            'status'           => 'accepted',
            'agreed_price_etb' => $negotiation->current_offer_etb,
            'accepted_at'      => now(),
        ]);

        CreateOrderFromAcceptedNegotiation::dispatch($negotiation->id);

        return response()->json(['data' => $negotiation->fresh(), 'message' => 'Negotiation accepted. Order being created.']);
    }

    public function aiSuggestion(string $id): JsonResponse
    {
        $negotiation = PriceNegotiation::with('turns', 'listing')->findOrFail($id);

        $suggestion = $this->ai->suggestNegotiationPrice([
            'initial_ask_etb'   => $negotiation->initial_ask_etb,
            'current_offer_etb' => $negotiation->current_offer_etb,
            'quantity_kg'       => $negotiation->quantity_kg,
            'turns'             => $negotiation->turns->toArray(),
            'commodity'         => $negotiation->listing->commodity ?? 'unknown',
        ]);

        $negotiation->update(['ai_suggestion' => $suggestion]);

        return response()->json(['data' => $suggestion]);
    }
}
