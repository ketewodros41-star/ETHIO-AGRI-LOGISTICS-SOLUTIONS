<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasTenant;
use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class ComplianceChecklist extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'compliance_checklists';

    protected $fillable = [
        'company_id','order_id','generated_by_agent',
        'items','overall_status','flagged_items',
    ];

    protected $casts = [
        'generated_by_agent' => 'boolean',
        'items'              => 'array',
        'flagged_items'      => 'array',
    ];
}
