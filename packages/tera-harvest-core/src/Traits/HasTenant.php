<?php

namespace Fleetbase\TeraHarvest\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasTenant
{
    public static function bootHasTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $companyId = static::currentTenantId();
            if ($companyId) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $companyId);
            }
        });

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $model->company_id = static::currentTenantId();
            }
        });
    }

    protected static function currentTenantId(): ?string
    {
        return session('company_id') ?? request()->header('X-Company-Id');
    }
}
