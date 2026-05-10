<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class CreditScoreHistory extends Model
{
    use HasUlid;

    protected $table = 'credit_score_history';

    public $timestamps = false;

    protected $fillable = [
        'farmer_id', 'company_id', 'score', 'score_band',
        'delta', 'reason', 'triggered_by_event', 'calculated_at',
    ];

    protected $casts = [
        'score'        => 'integer',
        'delta'        => 'integer',
        'calculated_at' => 'datetime',
        'created_at'   => 'datetime',
    ];
}
