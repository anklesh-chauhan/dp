<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangeControl>
 */
class ChangeControlFactory extends Factory
{
    protected $model = ChangeControl::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'change_number' => 'CC-'.fake()->unique()->numerify('#####'),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'rationale' => fake()->paragraph(),
            'status' => ChangeControlStatus::Draft,
            'department_id' => Department::factory(),
            'requested_by' => User::factory(),
            'owner_id' => User::factory(),
        ];
    }
}
