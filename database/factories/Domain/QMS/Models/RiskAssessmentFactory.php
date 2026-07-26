<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\RiskAssessmentType;
use App\Domain\QMS\Models\RiskAssessment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RiskAssessment> */
final class RiskAssessmentFactory extends Factory
{
    protected $model = RiskAssessment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => RiskAssessmentType::Process,
            'status' => RiskAssessmentStatus::Draft,
            'title' => fake()->sentence(5),
            'scope' => fake()->paragraph(),
            'hazard' => fake()->sentence(),
            'potential_harm' => fake()->paragraph(),
            'existing_controls' => fake()->sentence(),
            'department_id' => Department::factory(),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
            'approver_id' => null,
            'initial_severity' => fake()->numberBetween(1, 5),
            'initial_probability' => fake()->numberBetween(1, 5),
            'initial_detectability' => fake()->numberBetween(1, 5),
            'mitigation_plan' => null,
            'mitigation_due_at' => today()->addDays(45),
            'mitigation_completed_at' => null,
            'residual_severity' => null,
            'residual_probability' => null,
            'residual_detectability' => null,
            'review_due_at' => today()->addDays(90),
            'approved_at' => null,
            'closed_at' => null,
        ];
    }
}
