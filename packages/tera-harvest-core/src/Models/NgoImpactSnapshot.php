<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class NgoImpactSnapshot extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'ngo_impact_snapshots';

    public $timestamps = false;

    protected $fillable = [
        'programme_id', 'company_id', 'snapshot_period',
        'active_beneficiaries', 'graduated_beneficiaries',
        'avg_income_change_pct', 'avg_yield_change_pct',
        'total_transactions_etb', 'total_kg_traded',
        'women_beneficiaries', 'youth_beneficiaries',
        'kpi_values', 'generated_by', 'report_url',
    ];

    protected $casts = [
        'active_beneficiaries'   => 'integer',
        'graduated_beneficiaries' => 'integer',
        'avg_income_change_pct'  => 'decimal:2',
        'avg_yield_change_pct'   => 'decimal:2',
        'total_transactions_etb' => 'decimal:2',
        'total_kg_traded'        => 'decimal:3',
        'women_beneficiaries'    => 'integer',
        'youth_beneficiaries'    => 'integer',
        'kpi_values'             => 'array',
        'created_at'             => 'datetime',
    ];

    public function programme()
    {
        return $this->belongsTo(NgoProgramme::class, 'programme_id');
    }
}
