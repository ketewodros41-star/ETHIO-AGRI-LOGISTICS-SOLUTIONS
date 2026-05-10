<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class FarmerCreditScore extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'farmer_credit_scores';

    protected $fillable = [
        'farmer_id', 'company_id', 'score', 'score_band',
        'delivery_reliability_score', 'quality_consistency_score',
        'volume_history_score', 'payment_behaviour_score',
        'total_orders_completed', 'total_kg_traded', 'avg_quality_grade',
        'seasons_active', 'last_dispute_at', 'last_calculated_at', 'score_version',
    ];

    protected $casts = [
        'score'                       => 'integer',
        'delivery_reliability_score'  => 'integer',
        'quality_consistency_score'   => 'integer',
        'volume_history_score'        => 'integer',
        'payment_behaviour_score'     => 'integer',
        'total_orders_completed'      => 'integer',
        'total_kg_traded'             => 'decimal:3',
        'seasons_active'              => 'integer',
        'score_version'               => 'integer',
        'last_dispute_at'             => 'datetime',
        'last_calculated_at'          => 'datetime',
    ];

    public function history()
    {
        return $this->hasMany(CreditScoreHistory::class, 'farmer_id', 'farmer_id')
                    ->where('company_id', $this->company_id)
                    ->orderByDesc('calculated_at');
    }

    public function isEligibleForMicrofinance(): bool
    {
        return in_array($this->score_band, ['silver', 'gold', 'platinum']);
    }

    public function getComponentTotal(): int
    {
        return $this->delivery_reliability_score
            + $this->quality_consistency_score
            + $this->volume_history_score
            + $this->payment_behaviour_score;
    }
}
