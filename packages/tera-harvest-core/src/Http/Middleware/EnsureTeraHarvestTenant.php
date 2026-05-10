<?php

namespace Fleetbase\TeraHarvest\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeraHarvestTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $companyId = $request->header('X-Company-Id')
            ?? session('company_id')
            ?? optional(auth()->user())->company_uuid;

        if (!$companyId) {
            return response()->json(['error' => 'Tenant context required.'], 403);
        }

        $request->headers->set('X-Company-Id', $companyId);
        session(['company_id' => $companyId]);

        return $next($request);
    }
}
