<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class RouteSegment extends Model
{
    use HasUlid;

    protected $table = 'route_segments';

    protected $fillable = [
        'origin_woreda_id','destination_woreda_id','distance_km','road_type',
        'condition_score','avg_transit_hours','truck_accessible',
        'rainy_season_passable','notes','last_verified_at',
    ];

    protected $casts = [
        'distance_km'          => 'decimal:2',
        'avg_transit_hours'    => 'decimal:2',
        'truck_accessible'     => 'boolean',
        'rainy_season_passable'=> 'boolean',
        'last_verified_at'     => 'datetime',
        'condition_score'      => 'integer',
    ];

    public function origin()
    {
        return $this->belongsTo(EthiopiaWoreda::class, 'origin_woreda_id');
    }

    public function destination()
    {
        return $this->belongsTo(EthiopiaWoreda::class, 'destination_woreda_id');
    }

    public function reports()
    {
        return $this->hasMany(RouteReport::class, 'segment_id');
    }
}
