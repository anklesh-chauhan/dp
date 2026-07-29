<?php

declare(strict_types=1);

namespace App\Domain\QMS\Models;

use App\Domain\QMS\Enums\CsvExecutionResult;
use App\Models\User;
use Database\Factories\Domain\QMS\Models\CsvTestExecutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

final class CsvTestExecution extends Model
{
    /** @use HasFactory<CsvTestExecutionFactory> */
    use HasFactory;

    protected $fillable = [
        'execution_uuid', 'csv_validation_project_id', 'csv_test_case_id', 'execution_no',
        'environment', 'application_version', 'commit_sha', 'configuration_hash',
        'step_results', 'result', 'actual_result', 'evidence_summary', 'deviation_id',
        'executed_by', 'reviewed_by', 'started_at', 'completed_at', 'reviewed_at',
    ];

    protected static function booted(): void
    {
        self::creating(fn (self $execution) => $execution->execution_uuid ??= (string) Str::uuid());
        self::saving(function (self $execution): void {
            if ($execution->reviewed_at !== null && $execution->reviewed_by === $execution->executed_by) {
                throw ValidationException::withMessages([
                    'reviewed_by' => 'The test reviewer must be different from the executor.',
                ]);
            }

            if (in_array($execution->result, [CsvExecutionResult::Failed, CsvExecutionResult::Blocked], true)
                && $execution->completed_at !== null
                && $execution->deviation_id === null) {
                throw ValidationException::withMessages([
                    'deviation_id' => 'A failed or blocked execution must be linked to a deviation.',
                ]);
            }

            if ($execution->reviewed_at !== null && $execution->completed_at === null) {
                throw ValidationException::withMessages([
                    'completed_at' => 'An execution must be completed before independent review.',
                ]);
            }
        });
        self::updating(function (self $execution): void {
            if ($execution->getOriginal('reviewed_at') !== null) {
                throw new LogicException('Reviewed CSV test executions are immutable.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'step_results' => 'array',
            'result' => CsvExecutionResult::class,
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(CsvValidationProject::class, 'csv_validation_project_id');
    }

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(CsvTestCase::class, 'csv_test_case_id');
    }

    public function deviation(): BelongsTo
    {
        return $this->belongsTo(Deviation::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(QualityAttachment::class, 'attachable');
    }
}
