<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class ContractFulfilment extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'contract_fulfilments';

    protected $fillable = [
        'contract_id', 'lot_id', 'listing_id', 'company_id',
        'quantity_kg', 'quality_grade', 'price_per_kg_etb', 'total_etb',
        'status', 'rejection_reason', 'delivered_at',
    ];

    protected $casts = [
        'quantity_kg'       => 'decimal:3',
        'price_per_kg_etb'  => 'decimal:2',
        'total_etb'         => 'decimal:2',
        'delivered_at'      => 'datetime',
    ];

    public function contract()
    {
        return $this->belongsTo(ForwardContract::class, 'contract_id');
    }
}
