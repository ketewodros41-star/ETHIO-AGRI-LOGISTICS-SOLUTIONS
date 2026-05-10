<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class EthiopiaKebele extends Model
{
    use HasUlid;

    protected $table = 'ethiopia_kebeles';
    protected $fillable = ['woreda_id', 'name_en', 'name_am'];

    public function woreda()
    {
        return $this->belongsTo(EthiopiaWoreda::class, 'woreda_id');
    }

    public function landmarks()
    {
        return $this->hasMany(EthiopiaLandmark::class, 'kebele_id');
    }
}
