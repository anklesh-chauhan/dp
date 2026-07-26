<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductLicenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductLicense extends Model
{
    /** @use HasFactory<ProductLicenseFactory> */
    use HasFactory;

    protected $fillable = [
        'license_key',
        'key_id',
        'payload',
        'signature',
        'activated_at',
        'issued_at',
        'expires_at',
        'grace_ends_at',
        'revoked_at',
        'last_verified_at',
    ];

    protected $hidden = [
        'payload',
        'signature',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activated_at' => 'immutable_datetime',
            'issued_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'grace_ends_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'last_verified_at' => 'immutable_datetime',
        ];
    }

    /** @return HasMany<ProductLicenseAuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(ProductLicenseAuditEvent::class);
    }
}
