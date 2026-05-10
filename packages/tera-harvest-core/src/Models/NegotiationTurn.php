<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class NegotiationTurn extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'negotiation_turns';

    public $timestamps = false;

    protected $fillable = [
        'negotiation_id', 'company_id', 'turn_number', 'actor',
        'offered_price_etb', 'message', 'is_final',
    ];

    protected $casts = [
        'turn_number'       => 'integer',
        'offered_price_etb' => 'decimal:2',
        'is_final'          => 'boolean',
        'created_at'        => 'datetime',
    ];

    public function negotiation()
    {
        return $this->belongsTo(PriceNegotiation::class, 'negotiation_id');
    }
}
