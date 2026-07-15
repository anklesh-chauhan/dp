<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AiExecution;
use App\Models\AiExecutionAttempt;
use App\Services\AI\Enums\AiExecutionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiExecutionAttempt>
 */
final class AiExecutionAttemptFactory extends Factory
{
    protected $model = AiExecutionAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ai_execution_id' => AiExecution::factory(),
            'sequence' => 1,
            'provider' => 'fake',
            'model' => 'fake-model',
            'status' => AiExecutionStatus::RUNNING,
            'input_tokens' => null,
            'output_tokens' => null,
            'duration_ms' => null,
            'exception_class' => null,
            'error_message' => null,
            'started_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
        ];
    }
}
