<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InputSupplier extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'input_suppliers';

    protected $fillable = [
        'company_id', 'name', 'registration_number', 'type',
        'contact_phone', 'contact_email', 'region_id', 'woreda_id',
        'address', 'latitude', 'longitude', 'is_verified', 'is_active',
        'rating', 'total_orders_fulfilled',
    ];

    protected $casts = [
        'latitude'               => 'decimal:7',
        'longitude'              => 'decimal:7',
        'rating'                 => 'decimal:2',
        'is_verified'            => 'boolean',
        'is_active'              => 'boolean',
        'total_orders_fulfilled' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(InputProduct::class, 'supplier_id');
    }

    public function orders()
    {
        return $this->hasMany(InputOrder::class, 'supplier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }
}
