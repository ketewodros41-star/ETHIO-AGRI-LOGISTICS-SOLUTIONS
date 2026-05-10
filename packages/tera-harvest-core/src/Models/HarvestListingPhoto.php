<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class HarvestListingPhoto extends Model
{
    use HasUlid;

    protected $table = 'harvest_listing_photos';
    protected $fillable = ['listing_id', 'disk', 'path', 'url', 'order'];

    public function listing()
    {
        return $this->belongsTo(HarvestListing::class, 'listing_id');
    }
}
