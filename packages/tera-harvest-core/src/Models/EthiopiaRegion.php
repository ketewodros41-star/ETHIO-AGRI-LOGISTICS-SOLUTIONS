<?php

namespace Fleetbase\TeraHarvest\Models;

use Illuminate\Database\Eloquent\Model;

class EthiopiaRegion extends Model
{
    protected $table = 'ethiopia_regions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name_en', 'name_am', 'code', 'geometry'];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->id)) {
                $m->id = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function zones()
    {
        return $this->hasMany(EthiopiaZone::class, 'region_id');
    }
}
