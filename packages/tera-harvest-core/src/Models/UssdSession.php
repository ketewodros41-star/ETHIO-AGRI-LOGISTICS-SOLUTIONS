<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class UssdSession extends Model
{
    use HasUlid;

    protected $table = 'ussd_sessions';

    protected $fillable = [
        'session_id','phone_number','farmer_id','current_menu','session_data','status',
    ];

    protected $casts = [
        'session_data' => 'array',
    ];
}
