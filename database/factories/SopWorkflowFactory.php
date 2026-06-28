<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SopWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopWorkflow>
 */
class SopWorkflowFactory extends Factory
{
    protected $model = SopWorkflow::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
