<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Models\EthiopiaKebele;
use Fleetbase\TeraHarvest\Models\EthiopiaLandmark;
use Fleetbase\TeraHarvest\Models\EthiopiaRegion;
use Fleetbase\TeraHarvest\Models\EthiopiaWoreda;
use Fleetbase\TeraHarvest\Models\EthiopiaZone;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class EthiopiaController extends Controller
{
    public function __construct(private readonly TeraHarvestAIClient $ai) {}

    public function regions(): JsonResponse
    {
        return response()->json(EthiopiaRegion::orderBy('name_en')->get());
    }

    public function zones(string $regionId): JsonResponse
    {
        return response()->json(
            EthiopiaZone::where('region_id', $regionId)->orderBy('name_en')->get()
        );
    }

    public function woredas(string $zoneId): JsonResponse
    {
        return response()->json(
            EthiopiaWoreda::where('zone_id', $zoneId)->orderBy('name_en')->get()
        );
    }

    public function kebeles(string $woredaId): JsonResponse
    {
        return response()->json(
            EthiopiaKebele::where('woreda_id', $woredaId)->orderBy('name_en')->get()
        );
    }

    public function landmarks(Request $request): JsonResponse
    {
        $query = EthiopiaLandmark::query();

        if ($kebeleId = $request->query('kebele_id')) {
            $query->where('kebele_id', $kebeleId);
        }

        if ($request->filled(['lat', 'lng', 'radius'])) {
            $query->scopeNearby(
                (float) $request->query('lat'),
                (float) $request->query('lng'),
                (float) $request->query('radius', 5)
            );
        }

        return response()->json($query->orderBy('confirmed_count', 'desc')->get());
    }

    public function storeLandmark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_en'    => 'required|string|max:255',
            'name_am'    => 'required|string|max:255',
            'description'=> 'nullable|string',
            'latitude'   => 'required|numeric|between:-90,90',
            'longitude'  => 'required|numeric|between:-180,180',
            'kebele_id'  => 'nullable|string|exists:ethiopia_kebeles,id',
            'photo_url'  => 'nullable|url',
        ]);

        $landmark = EthiopiaLandmark::create(array_merge($validated, [
            'uuid'       => (string) Str::uuid(),
            'created_by' => auth()->id(),
        ]));

        return response()->json($landmark, 201);
    }

    public function confirmLandmark(string $id): JsonResponse
    {
        $landmark = EthiopiaLandmark::findOrFail($id);
        $landmark->increment('confirmed_count');
        return response()->json(['confirmed_count' => $landmark->confirmed_count]);
    }

    public function resolveLandmark(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string',
            'lat'         => 'nullable|numeric',
            'lng'         => 'nullable|numeric',
        ]);

        $result = $this->ai->resolveAddress(
            $validated['description'],
            $validated['lat'] ?? null,
            $validated['lng'] ?? null
        );

        return response()->json($result);
    }
}
