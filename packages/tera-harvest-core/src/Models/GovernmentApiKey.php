<?php

namespace Fleetbase\TeraHarvest\Models;

use Fleetbase\TeraHarvest\Traits\HasUlid;
use Fleetbase\TeraHarvest\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GovernmentApiKey extends Model
{
    use HasUlid, HasTenant, SoftDeletes;

    protected $table = 'government_api_keys';

    protected $fillable = [
        'company_id', 'ministry_name', 'contact_name', 'contact_email',
        'key_prefix', 'key_hash', 'scopes', 'rate_limit_per_minute',
        'rate_limit_per_day', 'total_requests', 'is_active', 'expires_at', 'last_used_at',
    ];

    protected $hidden = ['key_hash'];

    protected $casts = [
        'scopes'               => 'array',
        'rate_limit_per_minute' => 'integer',
        'rate_limit_per_day'   => 'integer',
        'total_requests'       => 'integer',
        'is_active'            => 'boolean',
        'expires_at'           => 'datetime',
        'last_used_at'         => 'datetime',
    ];

    public function dataRequests()
    {
        return $this->hasMany(GovernmentDataRequest::class, 'api_key_id');
    }

    public static function generateKey(): array
    {
        $raw    = 'gov_' . Str::random(40);
        $prefix = substr($raw, 0, 8);
        $hash   = hash('sha256', $raw);
        return compact('raw', 'prefix', 'hash');
    }

    public function verifyKey(string $raw): bool
    {
        return hash_equals($this->key_hash, hash('sha256', $raw));
    }

    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return true;
        }
        return in_array($scope, $this->scopes, true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && now()->isAfter($this->expires_at);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
