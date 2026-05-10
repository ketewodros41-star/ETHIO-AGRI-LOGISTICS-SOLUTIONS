<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class EthiopiaZone extends Model
{
    use HasUlid;

    protected $table = 'ethiopia_zones';
    protected $fillable = ['region_id', 'name_en', 'name_am', 'code'];

    public function region()
    {
        return $this->belongsTo(EthiopiaRegion::class, 'region_id');
    }

    public function woredas()
    {
        return $this->hasMany(EthiopiaWoreda::class, 'zone_id');
    }
}
