<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\Shared\Contracts\ElectronicSignatureRecord;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\ScheduleMGapAssessmentEventFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class ScheduleMGapAssessmentEvent extends Model implements ElectronicSignatureRecord
{
    /** @use HasFactory<ScheduleMGapAssessmentEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'event_uuid',
        'schedule_m_gap_assessment_id',
        'from_status',
        'to_status',
        'event_type',
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
        self::updating(fn () => throw new LogicException('Schedule M gap assessment events are append-only.'));
        self::deleting(fn () => throw new LogicException('Schedule M gap assessment events are append-only.'));
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => ScheduleMGapAssessmentStatus::class,
            'to_status' => ScheduleMGapAssessmentStatus::class,
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<ScheduleMGapAssessment, $this> */
    public function scheduleMGapAssessment(): BelongsTo
    {
        return $this->belongsTo(ScheduleMGapAssessment::class);
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
        return $this->signature_hash === null ? null : ($this->to_status?->value ?? $this->event_type);
    }

    public function signatureSignerId(): int|string|null
    {
        return $this->signature_hash === null ? null : $this->actor_id;
    }

    public function signatureTimestamp(): ?DateTimeInterface
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
