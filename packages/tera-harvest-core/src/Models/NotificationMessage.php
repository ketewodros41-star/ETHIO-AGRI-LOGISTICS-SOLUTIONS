<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasTenant;
use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class NotificationMessage extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'notification_messages';

    protected $fillable = [
        'company_id','recipient_id','recipient_phone','channel','language',
        'event_type','message_am','message_en','sent_message',
        'provider_response','status','provider','retry_count',
        'sent_at','delivered_at',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_at'           => 'datetime',
        'delivered_at'      => 'datetime',
        'retry_count'       => 'integer',
    ];
}
