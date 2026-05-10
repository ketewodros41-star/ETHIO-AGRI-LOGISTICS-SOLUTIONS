<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class RouteHistory extends Model
{
    use HasUlid;

    public $timestamps = false;
    protected $table = 'route_history';

    protected $fillable = [
        'order_id','driver_id','planned_segments','actual_path',
        'planned_hours','actual_hours','fuel_litres_used','incidents',
    ];

    protected $casts = [
        'planned_segments'  => 'array',
        'actual_path'       => 'array',
        'incidents'         => 'array',
        'planned_hours'     => 'decimal:2',
        'actual_hours'      => 'decimal:2',
        'fuel_litres_used'  => 'decimal:2',
        'created_at'        => 'datetime',
    ];
}
