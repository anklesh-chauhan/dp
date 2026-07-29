<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvTestType;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\CsvTestCaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CsvTestCase extends Model
{
    /** @use HasFactory<CsvTestCaseFactory> */
    use HasFactory;

    protected $fillable = [
        'csv_validation_project_id', 'test_identifier', 'version', 'type', 'title',
        'objective', 'preconditions', 'test_data', 'steps', 'criticality', 'status',
        'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CsvTestType::class,
            'steps' => 'array',
            'criticality' => CsvCriticality::class,
            'status' => CsvRequirementStatus::class,
            'approved_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(CsvValidationProject::class, 'csv_validation_project_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function requirements(): BelongsToMany
    {
        return $this->belongsToMany(CsvRequirement::class, 'csv_requirement_test_case')->withTimestamps();
    }

    public function executions(): HasMany
    {
        return $this->hasMany(CsvTestExecution::class);
    }
}
