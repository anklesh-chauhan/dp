<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Models\User;
use Database\Factories\Domain\QMS\Models\CsvRiskAssessmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CsvRiskAssessment extends Model
{
    /** @use HasFactory<CsvRiskAssessmentFactory> */
    use HasFactory;

    protected $fillable = [
        'csv_validation_project_id', 'csv_requirement_id', 'risk_identifier', 'hazard',
        'potential_impact', 'existing_controls', 'initial_severity', 'initial_probability',
        'initial_detectability', 'mitigation', 'residual_severity', 'residual_probability',
        'residual_detectability', 'acceptance_rationale', 'accepted_by', 'accepted_at',
    ];

    protected function casts(): array
    {
        return ['accepted_at' => 'immutable_datetime'];
    }

    public function initialRiskPriorityNumber(): int
    {
        return $this->initial_severity * $this->initial_probability * $this->initial_detectability;
    }

    public function residualRiskPriorityNumber(): ?int
    {
        if ($this->residual_severity === null
            || $this->residual_probability === null
            || $this->residual_detectability === null) {
            return null;
        }

        return $this->residual_severity * $this->residual_probability * $this->residual_detectability;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(CsvValidationProject::class, 'csv_validation_project_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(CsvRequirement::class, 'csv_requirement_id');
    }

    public function acceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
