<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Jobs\ExpireHarvestListings;
use Fleetbase\TeraHarvest\Jobs\NotifyMatchedBuyers;
use Fleetbase\TeraHarvest\Jobs\SuggestHarvestPrice;
use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Models\HarvestListingPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HarvestListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = HarvestListing::with(['photos', 'woreda', 'kebele'])->active();

        foreach (['crop_type', 'quality_grade', 'storage_type', 'woreda_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('region_id')) {
            $query->whereHas('woreda.zone.region', fn ($q) => $q->where('id', $request->input('region_id')));
        }

        if ($request->filled(['min_price', 'max_price'])) {
            $query->whereBetween('asking_price_etb', [$request->input('min_price'), $request->input('max_price')]);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', HarvestListing::class);

        $validated = $request->validate([
            'crop_type'         => 'required|in:teff,coffee,sesame,chickpeas,wheat,sorghum,maize,vegetables,fruits,pulses,spices,other',
            'quantity_kg'       => 'required|numeric|min:0.01',
            'asking_price_etb'  => 'required|numeric|min:0',
            'quality_grade'     => 'nullable|in:A,B,C,ungraded',
            'harvest_date'      => 'nullable|date',
            'availability_from' => 'nullable|date',
            'availability_until'=> 'nullable|date|after:availability_from',
            'woreda_id'         => 'nullable|string|exists:ethiopia_woredas,id',
            'kebele_id'         => 'nullable|string|exists:ethiopia_kebeles,id',
            'landmark_id'       => 'nullable|string|exists:ethiopia_landmarks,id',
            'storage_type'      => 'nullable|in:open_air,warehouse,cold_storage',
            'notes'             => 'nullable|string|max:2000',
        ]);

        $listing = HarvestListing::create(array_merge($validated, [
            'uuid'       => (string) Str::uuid(),
            'farmer_id'  => auth()->id(),
            'status'     => 'active',
            'created_by' => auth()->id(),
        ]));

        SuggestHarvestPrice::dispatch($listing->id);
        NotifyMatchedBuyers::dispatch($listing->id);

        return response()->json($listing->load(['photos', 'woreda']), 201);
    }

    public function show(string $id): JsonResponse
    {
        $listing = HarvestListing::with(['photos', 'woreda', 'kebele', 'landmark'])->findOrFail($id);
        $this->authorize('view', $listing);

        $listing->views()->create([
            'viewer_id'  => auth()->id(),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return response()->json($listing);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $listing = HarvestListing::findOrFail($id);
        $this->authorize('update', $listing);

        $validated = $request->validate([
            'quantity_kg'       => 'sometimes|numeric|min:0.01',
            'asking_price_etb'  => 'sometimes|numeric|min:0',
            'quality_grade'     => 'sometimes|in:A,B,C,ungraded',
            'availability_until'=> 'sometimes|date',
            'status'            => 'sometimes|in:draft,active,cancelled',
            'notes'             => 'sometimes|nullable|string|max:2000',
        ]);

        $listing->update($validated);
        return response()->json($listing);
    }

    public function destroy(string $id): JsonResponse
    {
        $listing = HarvestListing::findOrFail($id);
        $this->authorize('delete', $listing);
        $listing->delete();
        return response()->json(null, 204);
    }

    public function uploadPhoto(Request $request, string $id): JsonResponse
    {
        $listing = HarvestListing::findOrFail($id);
        $this->authorize('update', $listing);

        $request->validate(['photo' => 'required|image|max:5120']);

        $path = $request->file('photo')->store("harvest-listings/{$id}", 's3');
        $url  = Storage::disk('s3')->url($path);

        $photo = HarvestListingPhoto::create([
            'listing_id' => $id,
            'disk'       => 's3',
            'path'       => $path,
            'url'        => $url,
            'order'      => $listing->photos()->count(),
        ]);

        return response()->json($photo, 201);
    }

    public function deletePhoto(string $id, string $photoId): JsonResponse
    {
        $listing = HarvestListing::findOrFail($id);
        $this->authorize('update', $listing);

        $photo = HarvestListingPhoto::where('listing_id', $id)->findOrFail($photoId);
        Storage::disk($photo->disk)->delete($photo->path);
        $photo->delete();

        return response()->json(null, 204);
    }

    public function priceIntelligence(string $id): JsonResponse
    {
        $listing = HarvestListing::findOrFail($id);
        $this->authorize('view', $listing);

        return response()->json([
            'ai_suggested_price_etb' => $listing->ai_suggested_price_etb,
            'asking_price_etb'       => $listing->asking_price_etb,
        ]);
    }

    public function match(string $id): JsonResponse
    {
        $listing = HarvestListing::findOrFail($id);
        $this->authorize('update', $listing);

        NotifyMatchedBuyers::dispatch($listing->id);
        $listing->update(['status' => 'matched']);

        return response()->json(['status' => 'matched', 'id' => $id]);
    }
}
