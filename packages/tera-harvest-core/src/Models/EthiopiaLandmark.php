<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class EthiopiaLandmark extends Model
{
    use HasUlid;

    protected $table = 'ethiopia_landmarks';

    protected $fillable = [
        'kebele_id','name_en','name_am','description',
        'latitude','longitude','photo_url','confirmed_count','created_by',
    ];

    protected $casts = [
        'latitude'        => 'decimal:7',
        'longitude'       => 'decimal:7',
        'confirmed_count' => 'integer',
    ];

    public function kebele()
    {
        return $this->belongsTo(EthiopiaKebele::class, 'kebele_id');
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 5)
    {
        // Haversine approximation via bounding box pre-filter
        $latDelta = $radiusKm / 110.574;
        $lngDelta = $radiusKm / (111.320 * cos(deg2rad($lat)));

        return $query
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);
    }
}
