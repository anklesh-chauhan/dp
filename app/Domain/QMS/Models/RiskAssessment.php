<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\RiskAssessmentType;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\RiskAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class RiskAssessment extends Model
{
    /** @use HasFactory<RiskAssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'risk_number', 'type', 'status', 'title', 'scope', 'hazard', 'potential_harm',
        'existing_controls', 'department_id', 'owner_id', 'created_by', 'approver_id',
        'initial_severity', 'initial_probability', 'initial_detectability', 'mitigation_plan',
        'mitigation_due_at', 'mitigation_completed_at', 'residual_severity',
        'residual_probability', 'residual_detectability', 'review_due_at', 'approved_at',
        'closed_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $assessment): void {
            $assessment->risk_number ??= sprintf(
                'RA-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => RiskAssessmentType::class,
            'status' => RiskAssessmentStatus::class,
            'initial_severity' => 'integer',
            'initial_probability' => 'integer',
            'initial_detectability' => 'integer',
            'residual_severity' => 'integer',
            'residual_probability' => 'integer',
            'residual_detectability' => 'integer',
            'mitigation_due_at' => 'immutable_date',
            'mitigation_completed_at' => 'immutable_datetime',
            'review_due_at' => 'immutable_date',
            'approved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    public function initialRiskPriorityNumber(): int
    {
        return $this->initial_severity
            * $this->initial_probability
            * $this->initial_detectability;
    }

    public function residualRiskPriorityNumber(): ?int
    {
        if ($this->residual_severity === null
            || $this->residual_probability === null
            || $this->residual_detectability === null) {
            return null;
        }

        return $this->residual_severity
            * $this->residual_probability
            * $this->residual_detectability;
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    /** @return HasMany<RiskAssessmentEvent, $this> */
    public function auditEvents(): HasMany
    {
        return $this->hasMany(RiskAssessmentEvent::class);
    }

    /** @return MorphMany<QualityAttachment, $this> */
    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
