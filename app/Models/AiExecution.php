<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AI\Enums\LLMCapability;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\AiExecutionStatus;
use Database\Factories\AiExecutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class AiExecution extends Model
{
    /** @use HasFactory<AiExecutionFactory> */
    use HasFactory;

    protected $fillable = [
        'ulid',
        'use_case',
        'capability',
        'status',
        'attempt_count',
        'successful_provider',
        'successful_model',
        'input_tokens',
        'output_tokens',
        'duration_ms',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $execution): void {
            $execution->ulid ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'use_case' => AIUseCase::class,
            'capability' => LLMCapability::class,
            'status' => AiExecutionStatus::class,
            'attempt_count' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<AiExecutionAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(AiExecutionAttempt::class);
    }
}
