<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasTenant;
use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class PaymentWallet extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'payment_wallets';

    protected $fillable = [
        'company_id','owner_id','owner_type',
        'balance_etb','reserved_etb','currency','status',
    ];

    protected $casts = [
        'balance_etb'  => 'decimal:2',
        'reserved_etb' => 'decimal:2',
    ];

    public function transactions()
    {
        return $this->hasMany(PaymentTransaction::class, 'wallet_id');
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public function availableBalance(): string
    {
        return bcsub($this->balance_etb, $this->reserved_etb, 2);
    }
}
