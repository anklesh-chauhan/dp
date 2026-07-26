<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deviation>
 */
class DeviationFactory extends Factory
{
    protected $model = Deviation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'immediate_actions' => fake()->sentence(),
            'status' => DeviationStatus::Draft,
            'severity' => DeviationSeverity::Major,
            'occurred_at' => now()->subHour(),
            'discovered_at' => now(),
            'department_id' => Department::factory(),
            'reported_by' => User::factory(),
            'owner_id' => User::factory(),
            'investigation_due_at' => today()->addDays(30),
        ];
    }
}
