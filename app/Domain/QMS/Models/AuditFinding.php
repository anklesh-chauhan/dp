<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\AuditFindingSeverity;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\AuditFindingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class AuditFinding extends Model
{
    /** @use HasFactory<AuditFindingFactory> */
    use HasFactory;

    protected $fillable = [
        'finding_number', 'internal_audit_id', 'severity', 'classification', 'disposition',
        'clause_reference', 'title', 'description', 'objective_evidence', 'department_id',
        'owner_id', 'identified_by', 'identified_at', 'response_due_at', 'response',
        'responded_at', 'verified_by', 'verification_notes', 'verified_at', 'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $finding): void {
            $finding->finding_number ??= sprintf(
                'AF-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'severity' => AuditFindingSeverity::class,
            'classification' => AuditFindingClassification::class,
            'disposition' => AuditFindingDisposition::class,
            'identified_at' => 'immutable_datetime',
            'response_due_at' => 'immutable_date',
            'responded_at' => 'immutable_datetime',
            'verified_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<InternalAudit, $this> */
    public function internalAudit(): BelongsTo
    {
        return $this->belongsTo(InternalAudit::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** @return BelongsTo<User, $this> */
    public function identifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identified_by');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return HasMany<AuditFindingEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(AuditFindingEvent::class);
    }

    /** @return HasMany<Capa, $this> */
    public function capas(): HasMany
    {
        return $this->hasMany(Capa::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
