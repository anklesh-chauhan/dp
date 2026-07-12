<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\AiTaskStatus;
use Database\Factories\AiTaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

final class AiTask extends Model
{
    /** @use HasFactory<AiTaskFactory> */
    use HasFactory;

    protected $fillable = [
        'uuid',
        'use_case',
        'status',
        'input',
        'result',
        'provider',
        'model',
        'progress',
        'current_step',
        'error_message',
        'started_at',
        'completed_at',
        'failed_at',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $task): void {
            $task->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'use_case' => AIUseCase::class,
            'status' => AiTaskStatus::class,
            'input' => 'array',
            'result' => 'array',
            'progress' => 'integer',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'created_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by',
        );
    }

    public function markAsProcessing(
        string $currentStep,
        int $progress = 0,
    ): void {
        if ($this->status->isFinished()) {
            throw new LogicException(
                'A finished AI task cannot be marked as processing.',
            );
        }

        $this->forceFill([
            'status' => AiTaskStatus::PROCESSING,
            'progress' => $this->normalizeProgress($progress),
            'current_step' => $currentStep,
            'error_message' => null,
            'started_at' => $this->started_at ?? now(),
            'completed_at' => null,
            'failed_at' => null,
        ])->save();
    }

    public function updateProgress(
        int $progress,
        string $currentStep,
    ): void {
        if (! $this->status->isProcessing()) {
            throw new LogicException(
                'Only a processing AI task can update progress.',
            );
        }

        $this->forceFill([
            'progress' => $this->normalizeProgress($progress),
            'current_step' => $currentStep,
        ])->save();
    }

    /**
     * @param array<string, mixed> $result
     */
    public function markAsCompleted(
        array $result,
        ?string $provider = null,
        ?string $model = null,
    ): void {
        if ($this->status->isFinished()) {
            throw new LogicException(
                'A finished AI task cannot be completed again.',
            );
        }

        $this->forceFill([
            'status' => AiTaskStatus::COMPLETED,
            'result' => $result,
            'provider' => $provider,
            'model' => $model,
            'progress' => 100,
            'current_step' => null,
            'error_message' => null,
            'completed_at' => now(),
            'failed_at' => null,
        ])->save();
    }

    public function markAsFailed(string $errorMessage): void
    {
        if ($this->status->isFinished()) {
            throw new LogicException(
                'A finished AI task cannot be failed again.',
            );
        }

        $this->forceFill([
            'status' => AiTaskStatus::FAILED,
            'current_step' => null,
            'error_message' => $errorMessage,
            'failed_at' => now(),
            'completed_at' => null,
        ])->save();
    }

    public function markAsCancelled(): void
    {
        if ($this->status->isFinished()) {
            throw new LogicException(
                'A finished AI task cannot be cancelled.',
            );
        }

        $this->forceFill([
            'status' => AiTaskStatus::CANCELLED,
            'current_step' => null,
        ])->save();
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isProcessing(): bool
    {
        return $this->status->isProcessing();
    }

    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    public function isFinished(): bool
    {
        return $this->status->isFinished();
    }

    private function normalizeProgress(int $progress): int
    {
        return max(
            0,
            min(100, $progress),
        );
    }
}
