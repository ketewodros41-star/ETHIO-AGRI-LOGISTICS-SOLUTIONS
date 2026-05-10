<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class AggregationLotContribution extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'aggregation_lot_contributions';

    protected $fillable = [
        'lot_id', 'farmer_id', 'company_id', 'contributed_kg', 'quality_grade',
        'price_per_kg_etb', 'gross_payout_etb', 'commission_etb', 'net_payout_etb',
        'payout_transaction_id', 'payout_status', 'paid_at',
    ];

    protected $casts = [
        'contributed_kg'    => 'decimal:3',
        'price_per_kg_etb'  => 'decimal:2',
        'gross_payout_etb'  => 'decimal:2',
        'commission_etb'    => 'decimal:2',
        'net_payout_etb'    => 'decimal:2',
        'paid_at'           => 'datetime',
    ];

    public function lot()
    {
        return $this->belongsTo(AggregationLot::class, 'lot_id');
    }
}
