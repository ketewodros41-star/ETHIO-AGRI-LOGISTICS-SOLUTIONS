<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NgoProgramme extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'ngo_programmes';

    protected $fillable = [
        'company_id', 'ngo_name', 'programme_name', 'programme_code', 'description',
        'focus_area', 'region_id', 'budget_usd', 'budget_etb',
        'target_beneficiaries', 'enrolled_beneficiaries', 'start_date', 'end_date',
        'status', 'kpis',
    ];

    protected $casts = [
        'budget_usd'             => 'decimal:2',
        'budget_etb'             => 'decimal:2',
        'target_beneficiaries'   => 'integer',
        'enrolled_beneficiaries' => 'integer',
        'start_date'             => 'date',
        'end_date'               => 'date',
        'kpis'                   => 'array',
    ];

    public function beneficiaries()
    {
        return $this->hasMany(NgoBeneficiary::class, 'programme_id');
    }

    public function impactSnapshots()
    {
        return $this->hasMany(NgoImpactSnapshot::class, 'programme_id')->orderByDesc('created_at');
    }

    public function enrollmentRate(): float
    {
        if ($this->target_beneficiaries === 0) {
            return 0.0;
        }
        return round($this->enrolled_beneficiaries / $this->target_beneficiaries * 100, 2);
    }
}
