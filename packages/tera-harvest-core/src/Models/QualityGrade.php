<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasTenant;
use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class QualityGrade extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'quality_grades';

    protected $fillable = [
        'company_id','shipment_id','listing_id','inspector_id','crop_type',
        'grading_standard','overall_grade','weight_kg','moisture_pct',
        'foreign_matter_pct','defect_pct','colour_score','smell_score',
        'custom_attributes','notes','certificate_number','certificate_pdf_url',
        'certificate_hash','status','graded_at','created_by',
    ];

    protected $casts = [
        'custom_attributes' => 'array',
        'weight_kg'         => 'decimal:3',
        'moisture_pct'      => 'decimal:2',
        'foreign_matter_pct'=> 'decimal:2',
        'defect_pct'        => 'decimal:2',
        'graded_at'         => 'datetime',
    ];

    public function photos()
    {
        return $this->hasMany(QualityGradePhoto::class, 'grade_id');
    }

    public static function generateCertificateNumber(): string
    {
        $year = now()->year;
        $sequence = str_pad(static::whereYear('created_at', $year)->count() + 1, 6, '0', STR_PAD_LEFT);
        return "QC-{$year}-{$sequence}";
    }
}
