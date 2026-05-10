<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class ColdChainLog extends Model
{
    use HasUlid;

    public $timestamps = false;
    protected $table = 'cold_chain_logs';

    protected $fillable = [
        'shipment_id','sensor_id','temperature_celsius','humidity_pct',
        'recorded_at','lat','lng','alert_triggered','alert_acknowledged_at',
    ];

    protected $casts = [
        'temperature_celsius'   => 'decimal:2',
        'humidity_pct'          => 'decimal:2',
        'lat'                   => 'decimal:7',
        'lng'                   => 'decimal:7',
        'alert_triggered'       => 'boolean',
        'recorded_at'           => 'datetime',
        'alert_acknowledged_at' => 'datetime',
        'created_at'            => 'datetime',
    ];
}
