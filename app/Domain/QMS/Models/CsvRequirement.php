<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\CsvRequirementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CsvRequirement extends Model
{
    /** @use HasFactory<CsvRequirementFactory> */
    use HasFactory;

    protected $fillable = [
        'csv_validation_project_id', 'requirement_identifier', 'version', 'category',
        'statement', 'rationale', 'source_reference', 'acceptance_criteria', 'criticality',
        'gxp_relevant', 'data_integrity_relevant', 'status', 'owner_id', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'criticality' => CsvCriticality::class,
            'status' => CsvRequirementStatus::class,
            'gxp_relevant' => 'boolean',
            'data_integrity_relevant' => 'boolean',
            'approved_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(CsvValidationProject::class, 'csv_validation_project_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function risks(): HasMany
    {
        return $this->hasMany(CsvRiskAssessment::class);
    }

    public function testCases(): BelongsToMany
    {
        return $this->belongsToMany(CsvTestCase::class, 'csv_requirement_test_case')->withTimestamps();
    }
}
