<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\CsvValidationProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class CsvValidationProject extends Model
{
    /** @use HasFactory<CsvValidationProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'project_uuid', 'project_number', 'system_identifier', 'system_name', 'system_version',
        'intended_use', 'status', 'gxp_criticality', 'is_gxp', 'uses_electronic_records',
        'uses_electronic_signatures', 'regulatory_scope', 'validation_strategy',
        'release_baseline', 'validation_summary', 'department_id', 'business_owner_id',
        'system_owner_id', 'quality_owner_id', 'created_by', 'released_by',
        'validation_plan_document_id', 'summary_report_document_id', 'change_control_id',
        'planned_release_date', 'released_at', 'next_periodic_review_date', 'retired_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (self $project): void {
            $project->project_uuid ??= (string) Str::uuid();
            $project->project_number ??= sprintf(
                'CSV-%s-%s',
                now()->format('Y'),
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );
            $project->status ??= CsvValidationProjectStatus::Draft;
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CsvValidationProjectStatus::class,
            'gxp_criticality' => CsvCriticality::class,
            'is_gxp' => 'boolean',
            'uses_electronic_records' => 'boolean',
            'uses_electronic_signatures' => 'boolean',
            'regulatory_scope' => 'array',
            'release_baseline' => 'array',
            'planned_release_date' => 'immutable_date',
            'released_at' => 'immutable_datetime',
            'next_periodic_review_date' => 'immutable_date',
            'retired_at' => 'immutable_datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function businessOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'business_owner_id');
    }

    public function systemOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'system_owner_id');
    }

    public function qualityOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function validationPlanDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'validation_plan_document_id');
    }

    public function summaryReportDocument(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'summary_report_document_id');
    }

    public function changeControl(): BelongsTo
    {
        return $this->belongsTo(ChangeControl::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(CsvRequirement::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(CsvRiskAssessment::class);
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(CsvSpecification::class);
    }

    public function testCases(): HasMany
    {
        return $this->hasMany(CsvTestCase::class);
    }

    public function testExecutions(): HasMany
    {
        return $this->hasMany(CsvTestExecution::class);
    }

    public function periodicReviews(): HasMany
    {
        return $this->hasMany(CsvPeriodicReview::class);
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(CsvValidationProjectEvent::class);
    }
}
