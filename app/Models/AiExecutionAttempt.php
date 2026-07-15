<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AI\Enums\AiExecutionStatus;
use Database\Factories\AiExecutionAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AiExecutionAttempt extends Model
{
    /** @use HasFactory<AiExecutionAttemptFactory> */
    use HasFactory;

    protected $fillable = [
        'ai_execution_id',
        'sequence',
        'provider',
        'model',
        'status',
        'input_tokens',
        'output_tokens',
        'duration_ms',
        'exception_class',
        'error_message',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_execution_id' => 'integer',
            'sequence' => 'integer',
            'status' => AiExecutionStatus::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<AiExecution, $this>
     */
    public function execution(): BelongsTo
    {
        return $this->belongsTo(
            AiExecution::class,
            'ai_execution_id',
        );
    }
}
