<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AiExecution;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\AiExecutionStatus;
use App\Services\AI\Enums\LLMCapability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiExecution>
 */
final class AiExecutionFactory extends Factory
{
    protected $model = AiExecution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'use_case' => AIUseCase::cases()[0],
            'capability' => LLMCapability::cases()[0],
            'status' => AiExecutionStatus::RUNNING,
            'attempt_count' => 0,
            'successful_provider' => null,
            'successful_model' => null,
            'input_tokens' => null,
            'output_tokens' => null,
            'duration_ms' => null,
            'started_at' => now(),
            'completed_at' => null,
            'failed_at' => null,
        ];
    }
}
