<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ForwardContract extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'forward_contracts';

    protected $fillable = [
        'contract_number', 'company_id', 'buyer_id', 'commodity',
        'quantity_kg', 'price_per_kg_etb', 'total_value_etb',
        'deposit_pct', 'deposit_etb', 'deposit_transaction_id', 'deposit_status',
        'quality_grade', 'delivery_region_id',
        'delivery_start_date', 'delivery_end_date',
        'status', 'fulfilled_kg', 'cancellation_reason', 'cancelled_at',
    ];

    protected $casts = [
        'quantity_kg'        => 'decimal:3',
        'price_per_kg_etb'   => 'decimal:2',
        'total_value_etb'    => 'decimal:2',
        'deposit_pct'        => 'decimal:2',
        'deposit_etb'        => 'decimal:2',
        'fulfilled_kg'       => 'decimal:3',
        'delivery_start_date' => 'date',
        'delivery_end_date'   => 'date',
        'cancelled_at'       => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->contract_number)) {
                $model->contract_number = 'FWD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    public function fulfilments()
    {
        return $this->hasMany(ContractFulfilment::class, 'contract_id');
    }

    public function remainingKg(): string
    {
        return bcsub((string) $this->quantity_kg, (string) $this->fulfilled_kg, 3);
    }

    public function fulfillmentPercentage(): float
    {
        if (bccomp((string) $this->quantity_kg, '0', 3) === 0) {
            return 0.0;
        }
        return (float) bcdiv(
            bcmul((string) $this->fulfilled_kg, '100', 2),
            (string) $this->quantity_kg,
            2
        );
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->delivery_end_date);
    }
}
