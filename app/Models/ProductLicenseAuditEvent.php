<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductLicenseAuditEventType;
use App\Enums\ProductLicenseState;
use Database\Factories\ProductLicenseAuditEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ProductLicenseAuditEvent extends Model
{
    /** @use HasFactory<ProductLicenseAuditEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'product_license_id',
        'event_type',
        'from_state',
        'to_state',
        'context',
        'occurred_at',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('License audit events are append-only.'));
        static::deleting(fn () => throw new LogicException('License audit events are append-only.'));
    }

    protected function casts(): array
    {
        return [
            'event_type' => ProductLicenseAuditEventType::class,
            'from_state' => ProductLicenseState::class,
            'to_state' => ProductLicenseState::class,
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ProductLicense, $this> */
    public function license(): BelongsTo
    {
        return $this->belongsTo(ProductLicense::class, 'product_license_id');
    }
}
