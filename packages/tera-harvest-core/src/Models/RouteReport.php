<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class RouteReport extends Model
{
    use HasUlid;

    protected $table = 'route_reports';

    protected $fillable = [
        'segment_id','reported_by','report_type','description',
        'lat','lng','severity','is_verified','verified_by',
        'verified_at','photo_url','valid_from','valid_until',
    ];

    protected $casts = [
        'lat'          => 'decimal:7',
        'lng'          => 'decimal:7',
        'is_verified'  => 'boolean',
        'verified_at'  => 'datetime',
        'valid_from'   => 'datetime',
        'valid_until'  => 'datetime',
    ];

    public function segment()
    {
        return $this->belongsTo(RouteSegment::class, 'segment_id');
    }
}
