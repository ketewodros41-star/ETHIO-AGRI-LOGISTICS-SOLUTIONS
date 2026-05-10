<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class MicrofinanceCreditRequest extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'microfinance_credit_requests';

    protected $fillable = [
        'farmer_id', 'company_id', 'partner_name', 'partner_reference',
        'requested_amount_etb', 'score_at_request', 'score_band_at_request',
        'data_package', 'status', 'approved_amount_etb', 'interest_rate_pct',
        'disbursed_at', 'due_date', 'repaid_at',
    ];

    protected $casts = [
        'requested_amount_etb' => 'decimal:2',
        'approved_amount_etb'  => 'decimal:2',
        'interest_rate_pct'    => 'decimal:2',
        'score_at_request'     => 'integer',
        'data_package'         => 'array',
        'disbursed_at'         => 'datetime',
        'due_date'             => 'datetime',
        'repaid_at'            => 'datetime',
    ];

    public function creditScore()
    {
        return $this->belongsTo(FarmerCreditScore::class, 'farmer_id', 'farmer_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['approved', 'disbursed']);
    }
}
