<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class HarvestListingView extends Model
{
    use HasUlid;

    public $timestamps = false;
    protected $table = 'harvest_listing_views';
    protected $fillable = ['listing_id', 'viewer_id', 'ip_address', 'created_at'];

    public function listing()
    {
        return $this->belongsTo(HarvestListing::class, 'listing_id');
    }
}
