<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasTenant;
use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HarvestListing extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'harvest_listings';

    protected $fillable = [
        'company_id','farmer_id','cooperative_id','crop_type','quantity_kg',
        'asking_price_etb','ai_suggested_price_etb','quality_grade',
        'harvest_date','availability_from','availability_until',
        'woreda_id','kebele_id','landmark_id','storage_type','status','notes','created_by',
    ];

    protected $casts = [
        'quantity_kg'              => 'decimal:2',
        'asking_price_etb'         => 'decimal:2',
        'ai_suggested_price_etb'   => 'decimal:2',
        'harvest_date'             => 'date',
        'availability_from'        => 'date',
        'availability_until'       => 'date',
    ];

    public function photos()
    {
        return $this->hasMany(HarvestListingPhoto::class, 'listing_id')->orderBy('order');
    }

    public function views()
    {
        return $this->hasMany(HarvestListingView::class, 'listing_id');
    }

    public function woreda()
    {
        return $this->belongsTo(EthiopiaWoreda::class, 'woreda_id');
    }

    public function kebele()
    {
        return $this->belongsTo(EthiopiaKebele::class, 'kebele_id');
    }

    public function landmark()
    {
        return $this->belongsTo(EthiopiaLandmark::class, 'landmark_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
