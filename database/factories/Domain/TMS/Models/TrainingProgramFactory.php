<?php

declare(strict_types=1);

namespace Database\Factories\Domain\TMS\Models;

use App\Domain\TMS\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TrainingProgram> */
final class TrainingProgramFactory extends Factory
{
    protected $model = TrainingProgram::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('TRN-####')),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }
}
