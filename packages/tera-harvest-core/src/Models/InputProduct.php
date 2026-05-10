<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InputProduct extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'input_products';

    protected $fillable = [
        'supplier_id', 'company_id', 'name', 'sku', 'category',
        'description', 'unit', 'price_per_unit_etb', 'stock_quantity',
        'reorder_threshold', 'is_active', 'certifications',
    ];

    protected $casts = [
        'price_per_unit_etb' => 'decimal:2',
        'stock_quantity'     => 'decimal:3',
        'reorder_threshold'  => 'decimal:3',
        'is_active'          => 'boolean',
        'certifications'     => 'array',
    ];

    public function supplier()
    {
        return $this->belongsTo(InputSupplier::class, 'supplier_id');
    }

    public function isLowStock(): bool
    {
        return bccomp((string) $this->stock_quantity, (string) $this->reorder_threshold, 3) <= 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
