<?php

namespace Fleetbase\TeraHarvest\Http\Middleware;

use Fleetbase\TeraHarvest\Models\GovernmentApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class EnsureGovernmentApiKey
{
    public function handle(Request $request, \Closure $next): Response
    {
        $rawKey = $request->header('X-Gov-Api-Key') ?? $request->bearerToken();

        if (empty($rawKey)) {
            return response()->json(['message' => 'Government API key required.'], 401);
        }

        $prefix = substr($rawKey, 0, 8);
        $apiKey = GovernmentApiKey::active()
                                  ->where('key_prefix', $prefix)
                                  ->first();

        if (!$apiKey || !$apiKey->verifyKey($rawKey)) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        if ($apiKey->isExpired()) {
            return response()->json(['message' => 'API key has expired.'], 401);
        }

        // Per-minute rate limit
        $minuteKey = "gov_api:{$apiKey->id}:minute:" . now()->format('YmdHi');
        if (RateLimiter::tooManyAttempts($minuteKey, $apiKey->rate_limit_per_minute)) {
            return response()->json(['message' => 'Rate limit exceeded (per minute).'], 429);
        }
        RateLimiter::hit($minuteKey, 60);

        // Per-day rate limit
        $dayKey = "gov_api:{$apiKey->id}:day:" . now()->format('Ymd');
        if (RateLimiter::tooManyAttempts($dayKey, $apiKey->rate_limit_per_day)) {
            return response()->json(['message' => 'Daily rate limit exceeded.'], 429);
        }
        RateLimiter::hit($dayKey, 86400);

        $request->attributes->set('gov_api_key', $apiKey);
        $request->attributes->set('gov_company_id', $apiKey->company_id);

        return $next($request);
    }
}
