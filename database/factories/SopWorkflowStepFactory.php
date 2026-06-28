<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SopWorkflow;
use App\Models\SopWorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<SopWorkflowStep>
 */
class SopWorkflowStepFactory extends Factory
{
    protected $model = SopWorkflowStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workflow_id' => SopWorkflow::factory(),
            'step_no' => fake()->numberBetween(1, 4),
            'role_id' => Role::findOrCreate('qa reviewer', 'web')->id,
            'approval_type' => 'approval',
            'is_mandatory' => true,
        ];
    }
}
