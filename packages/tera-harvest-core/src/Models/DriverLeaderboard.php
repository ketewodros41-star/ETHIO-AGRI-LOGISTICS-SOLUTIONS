<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class DriverLeaderboard extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'driver_leaderboard';

    public $timestamps = false;

    protected $fillable = [
        'company_id', 'period_type', 'period_label', 'region_id', 'rank',
        'driver_id', 'display_name', 'deliveries_completed', 'total_distance_km',
        'net_earnings_etb', 'avg_rating', 'on_time_rate_pct', 'badges', 'score',
    ];

    protected $casts = [
        'rank'                 => 'integer',
        'deliveries_completed' => 'integer',
        'total_distance_km'    => 'decimal:3',
        'net_earnings_etb'     => 'decimal:2',
        'avg_rating'           => 'decimal:2',
        'on_time_rate_pct'     => 'decimal:2',
        'badges'               => 'array',
        'score'                => 'integer',
        'created_at'           => 'datetime',
    ];
}
