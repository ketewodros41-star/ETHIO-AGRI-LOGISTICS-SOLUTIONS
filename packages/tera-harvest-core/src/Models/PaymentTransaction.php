<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasUlid;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'wallet_id','order_id','type','amount_etb','fee_etb',
        'provider','provider_reference','provider_payload',
        'status','initiated_by','notes',
    ];

    protected $casts = [
        'amount_etb'       => 'decimal:2',
        'fee_etb'          => 'decimal:2',
        'provider_payload' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(PaymentWallet::class, 'wallet_id');
    }

    public function events()
    {
        return $this->hasMany(PaymentEvent::class, 'transaction_id');
    }

    public function appendEvent(string $eventType, array $payload): PaymentEvent
    {
        $previous = $this->events()->latest('created_at')->first();
        $previousHash = $previous?->hash ?? str_repeat('0', 64);
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $hash = hash('sha256', $previousHash . $payloadJson);

        return $this->events()->create([
            'uuid'           => (string) \Illuminate\Support\Str::uuid(),
            'event_type'     => $eventType,
            'payload'        => $payload,
            'hash'           => $hash,
            'previous_hash'  => $previousHash,
            'created_at'     => now(),
        ]);
    }
}
