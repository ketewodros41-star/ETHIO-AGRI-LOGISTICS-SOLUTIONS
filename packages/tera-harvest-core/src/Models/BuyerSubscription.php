<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class BuyerSubscription extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'buyer_subscriptions';

    protected $fillable = [
        'buyer_id', 'company_id', 'commodity', 'min_quantity_kg',
        'max_price_per_kg_etb', 'quality_grade', 'preferred_region_id',
        'frequency', 'is_active', 'last_notified_at',
    ];

    protected $casts = [
        'min_quantity_kg'       => 'decimal:3',
        'max_price_per_kg_etb'  => 'decimal:2',
        'is_active'             => 'boolean',
        'last_notified_at'      => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function matchesListing(HarvestListing $listing): bool
    {
        if ($this->commodity !== $listing->commodity) {
            return false;
        }
        if ($this->max_price_per_kg_etb !== null
            && bccomp((string) $listing->price_per_kg_etb, (string) $this->max_price_per_kg_etb, 2) > 0) {
            return false;
        }
        if ($this->quality_grade !== 'any' && $this->quality_grade !== $listing->quality_grade) {
            return false;
        }
        return true;
    }
}
