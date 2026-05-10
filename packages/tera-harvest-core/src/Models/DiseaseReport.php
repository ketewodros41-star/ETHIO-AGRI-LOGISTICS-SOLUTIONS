<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class DiseaseReport extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'disease_reports';

    protected $fillable = [
        'company_id', 'reported_by', 'crop_type', 'disease_name', 'severity',
        'symptoms', 'photo_urls', 'region_id', 'woreda_id',
        'latitude', 'longitude', 'affected_area_ha',
        'ai_diagnosis', 'ai_confidence', 'recommended_treatment',
        'status', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'photo_urls'           => 'array',
        'ai_diagnosis'         => 'array',
        'recommended_treatment' => 'array',
        'latitude'             => 'decimal:7',
        'longitude'            => 'decimal:7',
        'affected_area_ha'     => 'decimal:2',
        'ai_confidence'        => 'decimal:2',
        'verified_at'          => 'datetime',
    ];

    public function alerts()
    {
        return $this->hasMany(DiseaseAlert::class, 'source_report_id');
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }
}
