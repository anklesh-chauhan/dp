<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AiTask;
use App\Services\AI\Enums\AIUseCase;
use App\Services\AI\Enums\AiTaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiTask>
 */
final class AiTaskFactory extends Factory
{
    protected $model = AiTask::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'use_case' => AIUseCase::DOCUMENT_DESCRIPTION_GENERATION,
            'status' => AiTaskStatus::PENDING,
            'input' => [
                'name' => fake()->sentence(4),
                'department_name' => fake()->randomElement([
                    'Quality Assurance',
                    'Production',
                    'Quality Control',
                ]),
            ],
            'result' => null,
            'provider' => null,
            'model' => null,
            'progress' => 0,
            'current_step' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
            'failed_at' => null,
            'created_by' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (): array => [
            'status' => AiTaskStatus::PROCESSING,
            'progress' => 25,
            'current_step' => 'Generating document description',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => AiTaskStatus::COMPLETED,
            'result' => [
                'description' => fake()->paragraph(),
            ],
            'provider' => 'gemini',
            'model' => 'gemini-3.5-flash',
            'progress' => 100,
            'current_step' => null,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => AiTaskStatus::FAILED,
            'progress' => 25,
            'current_step' => null,
            'error_message' => 'AI provider unavailable.',
            'started_at' => now(),
            'failed_at' => now(),
        ]);
    }
}
