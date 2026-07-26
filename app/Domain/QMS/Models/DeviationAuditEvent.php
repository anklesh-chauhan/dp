<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\Shared\Contracts\ElectronicSignatureRecord;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\DeviationAuditEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class DeviationAuditEvent extends Model implements ElectronicSignatureRecord
{
    /** @use HasFactory<DeviationAuditEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'event_uuid',
        'deviation_id',
        'from_status',
        'to_status',
        'actor_id',
        'reason',
        'context',
        'signature_hash',
        'signature_ip_address',
        'signature_user_agent',
        'occurred_at',
    ];

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Deviation audit events are append-only.'));
        self::deleting(fn () => throw new LogicException('Deviation audit events are append-only.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => DeviationStatus::class,
            'to_status' => DeviationStatus::class,
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Deviation, $this> */
    public function deviation(): BelongsTo
    {
        return $this->belongsTo(Deviation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function signatureRecordKey(): int|string|null
    {
        return $this->event_uuid;
    }

    public function signatureMeaning(): ?string
    {
        return $this->signature_hash === null ? null : $this->to_status->value;
    }

    public function signatureSignerId(): int|string|null
    {
        return $this->signature_hash === null ? null : $this->actor_id;
    }

    public function signatureTimestamp(): ?\DateTimeInterface
    {
        return $this->signature_hash === null ? null : $this->occurred_at;
    }

    public function signatureHash(): ?string
    {
        return $this->signature_hash;
    }

    public function signatureReason(): ?string
    {
        return $this->signature_hash === null ? null : $this->reason;
    }

    public function signatureIpAddress(): ?string
    {
        return $this->signature_ip_address;
    }

    public function signatureUserAgent(): ?string
    {
        return $this->signature_user_agent;
    }
}
