<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class DriverEarningsSummary extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'driver_earnings_summary';

    protected $fillable = [
        'driver_id', 'company_id', 'period_type', 'period_label', 'year', 'week', 'month',
        'deliveries_completed', 'total_distance_km', 'base_earnings_etb',
        'bonus_etb', 'deductions_etb', 'net_earnings_etb',
        'avg_rating', 'on_time_deliveries', 'late_deliveries',
        'badges_earned', 'disbursed', 'disbursed_at', 'disbursement_transaction_id',
    ];

    protected $casts = [
        'year'                        => 'integer',
        'week'                        => 'integer',
        'month'                       => 'integer',
        'deliveries_completed'        => 'integer',
        'total_distance_km'           => 'decimal:3',
        'base_earnings_etb'           => 'decimal:2',
        'bonus_etb'                   => 'decimal:2',
        'deductions_etb'              => 'decimal:2',
        'net_earnings_etb'            => 'decimal:2',
        'avg_rating'                  => 'decimal:2',
        'on_time_deliveries'          => 'integer',
        'late_deliveries'             => 'integer',
        'badges_earned'               => 'array',
        'disbursed'                   => 'boolean',
        'disbursed_at'                => 'datetime',
    ];

    public function onTimeRate(): float
    {
        $total = $this->on_time_deliveries + $this->late_deliveries;
        if ($total === 0) {
            return 100.0;
        }
        return round($this->on_time_deliveries / $total * 100, 2);
    }

    public function performanceScore(): int
    {
        $ratingScore   = (int) bcmul((string) $this->avg_rating, '10', 0);
        $onTimeScore   = (int) ($this->onTimeRate() / 100 * 30);
        $volumeScore   = min(20, $this->deliveries_completed);
        return min(100, $ratingScore + $onTimeScore + $volumeScore);
    }
}
