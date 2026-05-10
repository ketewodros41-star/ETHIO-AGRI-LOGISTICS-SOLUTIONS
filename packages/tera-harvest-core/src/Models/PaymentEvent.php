<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    use HasUlid;

    public $timestamps = false;  // append-only, only created_at
    protected $table = 'payment_events';

    protected $fillable = [
        'uuid','transaction_id','event_type','payload','hash','previous_hash','created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }

    public function verifyChainIntegrity(): bool
    {
        $payloadJson = json_encode($this->payload, JSON_UNESCAPED_UNICODE);
        $expected = hash('sha256', ($this->previous_hash ?? str_repeat('0', 64)) . $payloadJson);
        return hash_equals($expected, $this->hash);
    }
}
