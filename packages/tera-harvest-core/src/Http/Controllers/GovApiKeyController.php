<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\GovernmentApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class GovApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-Id');
        $keys      = GovernmentApiKey::where('company_id', $companyId)->paginate(20);
        return response()->json(['data' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ministry_name'         => 'required|string|max:255',
            'contact_name'          => 'nullable|string|max:255',
            'contact_email'         => 'nullable|email',
            'scopes'                => 'nullable|array',
            'rate_limit_per_minute' => 'nullable|integer|min:1',
            'rate_limit_per_day'    => 'nullable|integer|min:1',
            'expires_at'            => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $keyData = GovernmentApiKey::generateKey();

        $apiKey = GovernmentApiKey::create(array_merge(
            $request->only(['ministry_name', 'contact_name', 'contact_email', 'scopes',
                            'rate_limit_per_minute', 'rate_limit_per_day', 'expires_at']),
            [
                'company_id' => $request->header('X-Company-Id'),
                'key_prefix' => $keyData['prefix'],
                'key_hash'   => $keyData['hash'],
            ]
        ));

        return response()->json([
            'data'    => $apiKey,
            'api_key' => $keyData['raw'],
            'message' => 'Store this key securely — it will not be shown again.',
        ], 201);
    }

    public function revoke(string $id, Request $request): JsonResponse
    {
        $apiKey = GovernmentApiKey::findOrFail($id);
        $apiKey->update(['is_active' => false]);
        return response()->json(['message' => 'API key revoked.']);
    }

    public function usageStats(string $id): JsonResponse
    {
        $apiKey = GovernmentApiKey::findOrFail($id);
        $stats  = $apiKey->dataRequests()
                         ->selectRaw('endpoint, COUNT(*) as requests, AVG(response_time_ms) as avg_ms')
                         ->groupBy('endpoint')
                         ->get();

        return response()->json(['data' => $stats, 'total_requests' => $apiKey->total_requests]);
    }
}
