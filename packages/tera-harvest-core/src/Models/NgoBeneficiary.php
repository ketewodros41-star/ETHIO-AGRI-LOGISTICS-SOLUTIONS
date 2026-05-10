<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class NgoBeneficiary extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'ngo_beneficiaries';

    protected $fillable = [
        'programme_id', 'farmer_id', 'company_id', 'status',
        'baseline_data', 'current_data',
        'income_baseline_etb', 'income_current_etb',
        'yield_baseline_kg', 'yield_current_kg',
        'enrolled_at', 'graduated_at',
    ];

    protected $casts = [
        'baseline_data'       => 'array',
        'current_data'        => 'array',
        'income_baseline_etb' => 'decimal:2',
        'income_current_etb'  => 'decimal:2',
        'yield_baseline_kg'   => 'decimal:3',
        'yield_current_kg'    => 'decimal:3',
        'enrolled_at'         => 'datetime',
        'graduated_at'        => 'datetime',
    ];

    public function programme()
    {
        return $this->belongsTo(NgoProgramme::class, 'programme_id');
    }

    public function incomeChangePercentage(): ?float
    {
        if ($this->income_baseline_etb === null || bccomp((string) $this->income_baseline_etb, '0', 2) === 0) {
            return null;
        }
        $change = bcsub((string) $this->income_current_etb, (string) $this->income_baseline_etb, 2);
        return (float) bcdiv(bcmul($change, '100', 2), (string) $this->income_baseline_etb, 2);
    }
}
