<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class GovernmentDataRequest extends Model
{
    use HasUlid;

    protected $table = 'government_data_requests';

    public $timestamps = false;

    protected $fillable = [
        'api_key_id', 'company_id', 'endpoint', 'query_params',
        'response_code', 'response_time_ms', 'records_returned', 'ip_address',
    ];

    protected $casts = [
        'query_params'     => 'array',
        'response_code'    => 'integer',
        'response_time_ms' => 'integer',
        'records_returned' => 'integer',
        'created_at'       => 'datetime',
    ];

    public function apiKey()
    {
        return $this->belongsTo(GovernmentApiKey::class, 'api_key_id');
    }
}
