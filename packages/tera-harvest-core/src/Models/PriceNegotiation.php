<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class PriceNegotiation extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'price_negotiations';

    protected $fillable = [
        'company_id', 'listing_id', 'buyer_id', 'seller_id',
        'initial_ask_etb', 'current_offer_etb', 'agreed_price_etb',
        'quantity_kg', 'status', 'order_id', 'max_turns', 'current_turn',
        'ai_suggestion', 'expires_at', 'accepted_at',
    ];

    protected $casts = [
        'initial_ask_etb'   => 'decimal:2',
        'current_offer_etb' => 'decimal:2',
        'agreed_price_etb'  => 'decimal:2',
        'quantity_kg'       => 'decimal:3',
        'max_turns'         => 'integer',
        'current_turn'      => 'integer',
        'ai_suggestion'     => 'array',
        'expires_at'        => 'datetime',
        'accepted_at'       => 'datetime',
    ];

    public function turns()
    {
        return $this->hasMany(NegotiationTurn::class, 'negotiation_id')->orderBy('turn_number');
    }

    public function listing()
    {
        return $this->belongsTo(HarvestListing::class, 'listing_id');
    }

    public function hasReachedMaxTurns(): bool
    {
        return $this->current_turn >= $this->max_turns;
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->expires_at);
    }

    public function midpoint(): string
    {
        return bcdiv(
            bcadd((string) $this->initial_ask_etb, (string) $this->current_offer_etb, 2),
            '2',
            2
        );
    }
}
