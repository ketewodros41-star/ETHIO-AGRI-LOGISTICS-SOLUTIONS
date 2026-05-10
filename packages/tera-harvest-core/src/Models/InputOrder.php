<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InputOrder extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'input_orders';

    protected $fillable = [
        'order_number', 'farmer_id', 'supplier_id', 'company_id', 'status',
        'subtotal_etb', 'delivery_fee_etb', 'total_etb',
        'payment_method', 'payment_transaction_id', 'payment_status',
        'delivery_address', 'delivery_woreda_id', 'driver_id',
        'expected_delivery_at', 'delivered_at', 'notes',
    ];

    protected $casts = [
        'subtotal_etb'         => 'decimal:2',
        'delivery_fee_etb'     => 'decimal:2',
        'total_etb'            => 'decimal:2',
        'expected_delivery_at' => 'datetime',
        'delivered_at'         => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->order_number)) {
                $model->order_number = 'INP-' . strtoupper(substr(uniqid(), -8));
            }
        });
    }

    public function supplier()
    {
        return $this->belongsTo(InputSupplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(InputOrderItem::class, 'order_id');
    }
}
