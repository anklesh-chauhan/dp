<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\CapaType;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\CapaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use LogicException;

final class Capa extends Model
{
    /** @use HasFactory<CapaFactory> */
    use HasFactory;

    protected $fillable = [
        'capa_number',
        'deviation_id',
        'audit_finding_id',
        'investigation_id',
        'type',
        'status',
        'title',
        'action_plan',
        'owner_id',
        'due_at',
        'completed_at',
        'effectiveness_due_at',
        'effectiveness_verified_at',
        'effectiveness_result',
        'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $capa): void {
            if (($capa->deviation_id === null) === ($capa->audit_finding_id === null)) {
                throw new LogicException('A CAPA must have exactly one quality-event source.');
            }

            $capa->capa_number ??= sprintf(
                'CAPA-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });

        self::updating(function (self $capa): void {
            if ($capa->isDirty(['deviation_id', 'audit_finding_id'])) {
                throw new LogicException('A CAPA quality-event source is immutable.');
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CapaType::class,
            'status' => CapaStatus::class,
            'due_at' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
            'effectiveness_due_at' => 'immutable_date',
            'effectiveness_verified_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Deviation, $this> */
    public function deviation(): BelongsTo
    {
        return $this->belongsTo(Deviation::class);
    }

    /** @return BelongsTo<Investigation, $this> */
    public function investigation(): BelongsTo
    {
        return $this->belongsTo(Investigation::class);
    }

    /** @return BelongsTo<AuditFinding, $this> */
    public function auditFinding(): BelongsTo
    {
        return $this->belongsTo(AuditFinding::class);
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<CapaAuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(CapaAuditEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
