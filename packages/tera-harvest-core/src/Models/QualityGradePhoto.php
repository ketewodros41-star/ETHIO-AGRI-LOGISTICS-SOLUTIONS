<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class QualityGradePhoto extends Model
{
    use HasUlid;

    public $timestamps = false;
    protected $table = 'quality_grade_photos';
    protected $fillable = ['grade_id', 'disk', 'path', 'url', 'caption', 'created_at'];

    public function grade()
    {
        return $this->belongsTo(QualityGrade::class, 'grade_id');
    }
}
