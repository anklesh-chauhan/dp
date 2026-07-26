<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\DeviationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use LogicException;

final class Deviation extends Model implements ApprovableSubject
{
    /** @use HasFactory<DeviationFactory> */
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'deviation_number',
        'title',
        'description',
        'immediate_actions',
        'status',
        'severity',
        'occurred_at',
        'discovered_at',
        'department_id',
        'reported_by',
        'owner_id',
        'investigation_due_at',
        'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $deviation): void {
            $deviation->deviation_number ??= sprintf(
                'DEV-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });

        self::updating(function (self $deviation): void {
            if ($deviation->isDirty('complaint_id') && $deviation->getOriginal('complaint_id') !== null) {
                throw new LogicException('A deviation complaint source is immutable.');
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => DeviationStatus::class,
            'severity' => DeviationSeverity::class,
            'occurred_at' => 'immutable_datetime',
            'discovered_at' => 'immutable_datetime',
            'investigation_due_at' => 'immutable_date',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<Complaint, $this> */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /** @return HasMany<Investigation, $this> */
    public function investigations(): HasMany
    {
        return $this->hasMany(Investigation::class);
    }

    /** @return HasMany<Capa, $this> */
    public function capas(): HasMany
    {
        return $this->hasMany(Capa::class);
    }

    /** @return HasMany<DeviationAuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(DeviationAuditEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }

    /** @return MorphMany<QualityApprovalInstance, $this> */
    public function approvalInstances(): MorphMany
    {
        return $this->morphMany(QualityApprovalInstance::class, 'subject');
    }

    public function approvalSubjectKey(): int|string|null
    {
        $key = $this->getKey();

        return is_int($key) || is_string($key) ? $key : null;
    }

    public function approvalSubjectReference(): string
    {
        return (string) $this->deviation_number;
    }

    public function approvalSubjectTitle(): string
    {
        return (string) $this->title;
    }

    public function approvalSubjectDepartmentId(): ?int
    {
        return $this->department_id === null ? null : (int) $this->department_id;
    }

    public function approvalSubjectCreatedById(): ?int
    {
        return $this->reported_by === null ? null : (int) $this->reported_by;
    }

    public function approvalSubjectOwnerId(): ?int
    {
        return $this->owner_id === null ? null : (int) $this->owner_id;
    }
}
