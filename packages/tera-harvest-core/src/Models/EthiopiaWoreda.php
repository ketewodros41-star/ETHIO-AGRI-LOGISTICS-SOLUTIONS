<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class EthiopiaWoreda extends Model
{
    use HasUlid;

    protected $table = 'ethiopia_woredas';
    protected $fillable = ['zone_id', 'name_en', 'name_am', 'code'];

    public function zone()
    {
        return $this->belongsTo(EthiopiaZone::class, 'zone_id');
    }

    public function kebeles()
    {
        return $this->hasMany(EthiopiaKebele::class, 'woreda_id');
    }
}
