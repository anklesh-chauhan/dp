<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ValidationMasterPlan> */
final class ValidationMasterPlanFactory extends Factory
{
    protected $model = ValidationMasterPlan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'status' => ValidationMasterPlanStatus::Draft,
            'period_start_at' => now()->startOfYear(),
            'period_end_at' => now()->endOfYear(),
            'scope' => fake()->paragraph(),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
