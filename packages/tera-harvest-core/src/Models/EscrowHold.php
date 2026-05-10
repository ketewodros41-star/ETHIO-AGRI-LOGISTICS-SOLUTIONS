<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class EscrowHold extends Model
{
    use HasUlid;

    protected $table = 'escrow_holds';

    protected $fillable = [
        'order_id','buyer_wallet_id','seller_wallet_id',
        'driver_commission_etb','broker_commission_etb','amount_etb',
        'status','held_at','released_at','release_trigger','released_by',
    ];

    protected $casts = [
        'driver_commission_etb' => 'decimal:2',
        'broker_commission_etb' => 'decimal:2',
        'amount_etb'            => 'decimal:2',
        'held_at'               => 'datetime',
        'released_at'           => 'datetime',
    ];

    public function buyerWallet()
    {
        return $this->belongsTo(PaymentWallet::class, 'buyer_wallet_id');
    }

    public function sellerWallet()
    {
        return $this->belongsTo(PaymentWallet::class, 'seller_wallet_id');
    }
}
