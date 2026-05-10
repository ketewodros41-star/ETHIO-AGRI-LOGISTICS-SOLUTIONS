<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasTenant;
use Fleetbase\TeraHarvest\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;

class ComplianceDocument extends Model
{
    use HasUlid, HasTenant;

    protected $table = 'compliance_documents';

    protected $fillable = [
        'company_id','order_id','document_type','document_number',
        'file_url','file_hash','chain_hash','status',
        'issued_by','issued_at','expires_at','authority','authority_reference',
    ];

    protected $casts = [
        'issued_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function generateDocumentNumber(string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $year = now()->year;
        $seq = str_pad(static::where('document_type', $type)->whereYear('created_at', $year)->count() + 1, 6, '0', STR_PAD_LEFT);
        return "{$prefix}-{$year}-{$seq}";
    }

    public function verifyHash(): bool
    {
        if (!$this->file_url || !$this->file_hash) {
            return false;
        }
        $contents = file_get_contents($this->file_url);
        return $contents !== false && hash_equals(hash('sha256', $contents), $this->file_hash);
    }
}
