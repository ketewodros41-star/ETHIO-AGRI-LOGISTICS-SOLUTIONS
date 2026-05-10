<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class InputOrderItem extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'input_order_items';

    protected $fillable = [
        'order_id', 'product_id', 'company_id', 'product_name', 'product_sku',
        'quantity', 'unit', 'unit_price_etb', 'line_total_etb',
    ];

    protected $casts = [
        'quantity'       => 'decimal:3',
        'unit_price_etb' => 'decimal:2',
        'line_total_etb' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(InputOrder::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(InputProduct::class, 'product_id');
    }
}
