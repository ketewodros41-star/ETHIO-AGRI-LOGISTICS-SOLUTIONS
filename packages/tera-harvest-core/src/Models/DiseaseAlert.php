<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class DiseaseAlert extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'disease_alerts';

    protected $fillable = [
        'company_id', 'source_report_id', 'crop_type', 'disease_name', 'severity',
        'region_id', 'radius_km', 'alert_message', 'alert_message_am',
        'farmers_notified', 'escalation_status', 'government_notified', 'expires_at',
    ];

    protected $casts = [
        'radius_km'            => 'decimal:2',
        'farmers_notified'     => 'integer',
        'government_notified'  => 'boolean',
        'expires_at'           => 'datetime',
    ];

    public function sourceReport()
    {
        return $this->belongsTo(DiseaseReport::class, 'source_report_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->isAfter($this->expires_at);
    }
}
