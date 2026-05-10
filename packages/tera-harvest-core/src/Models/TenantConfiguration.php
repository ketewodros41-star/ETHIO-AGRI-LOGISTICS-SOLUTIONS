<?php

namespace Fleetbase\TeraHarvest\Models;

use Illuminate\Database\Eloquent\Model;

class TenantConfiguration extends Model
{
    protected $table = 'tenant_configurations';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'company_id','timezone','currency','default_language',
        'active_payment_providers','active_crop_types','grading_standard',
        'custom_grade_config','escrow_release_trigger','driver_commission_pct',
        'broker_commission_pct','sms_sender_id','logo_url','primary_colour',
        'ecx_notifications_enabled','compliance_document_types',
    ];

    protected $casts = [
        'active_payment_providers'   => 'array',
        'active_crop_types'          => 'array',
        'custom_grade_config'        => 'array',
        'compliance_document_types'  => 'array',
        'ecx_notifications_enabled'  => 'boolean',
        'driver_commission_pct'      => 'decimal:2',
        'broker_commission_pct'      => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }
}
