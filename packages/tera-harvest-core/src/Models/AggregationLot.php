<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AggregationLot extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'aggregation_lots';

    protected $fillable = [
        'company_id', 'lot_number', 'commodity', 'collection_point_id',
        'target_kg', 'collected_kg', 'quality_grade', 'status',
        'buyer_id', 'agreed_price_per_kg_etb', 'total_sale_etb',
        'platform_commission_etb', 'net_farmer_payout_etb',
        'dispatched_at', 'sold_at', 'settled_at',
    ];

    protected $casts = [
        'target_kg'                  => 'decimal:3',
        'collected_kg'               => 'decimal:3',
        'agreed_price_per_kg_etb'    => 'decimal:2',
        'total_sale_etb'             => 'decimal:2',
        'platform_commission_etb'    => 'decimal:2',
        'net_farmer_payout_etb'      => 'decimal:2',
        'dispatched_at'              => 'datetime',
        'sold_at'                    => 'datetime',
        'settled_at'                 => 'datetime',
    ];

    public function contributions()
    {
        return $this->hasMany(AggregationLotContribution::class, 'lot_id');
    }

    public function remainingCapacityKg(): string
    {
        return bcsub((string) $this->target_kg, (string) $this->collected_kg, 3);
    }

    public function fillPercentage(): float
    {
        if (bccomp((string) $this->target_kg, '0', 3) === 0) {
            return 0.0;
        }
        return (float) bcdiv(
            bcmul((string) $this->collected_kg, '100', 2),
            (string) $this->target_kg,
            2
        );
    }
}
