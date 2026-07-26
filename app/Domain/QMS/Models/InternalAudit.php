<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\InternalAuditType;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\InternalAuditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class InternalAudit extends Model
{
    /** @use HasFactory<InternalAuditFactory> */
    use HasFactory;

    protected $fillable = [
        'audit_number',
        'type',
        'status',
        'title',
        'scope',
        'objectives',
        'criteria',
        'department_id',
        'lead_auditor_id',
        'created_by',
        'owner_id',
        'scheduled_start_at',
        'scheduled_end_at',
        'started_at',
        'completed_at',
        'report_issued_at',
        'follow_up_due_at',
        'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $audit): void {
            $audit->audit_number ??= sprintf(
                'AUD-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => InternalAuditType::class,
            'status' => InternalAuditStatus::class,
            'scheduled_start_at' => 'immutable_date',
            'scheduled_end_at' => 'immutable_date',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'report_issued_at' => 'immutable_datetime',
            'follow_up_due_at' => 'immutable_date',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, $this> */
    public function leadAuditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lead_auditor_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return HasMany<InternalAuditEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(InternalAuditEvent::class);
    }

    /** @return HasMany<AuditFinding, $this> */
    public function findings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
