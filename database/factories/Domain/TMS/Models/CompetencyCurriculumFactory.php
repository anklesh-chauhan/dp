<?php

declare(strict_types=1);

namespace Database\Factories\Domain\TMS\Models;

use App\Domain\TMS\Models\CompetencyCurriculum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompetencyCurriculum> */
final class CompetencyCurriculumFactory extends Factory
{
    protected $model = CompetencyCurriculum::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CURR-####')),
            'name' => fake()->words(3, true),
            'role_name' => fake()->optional()->jobTitle(),
            'description' => fake()->optional()->sentence(),
            'requalification_months' => fake()->optional()->numberBetween(6, 36),
            'gate_key' => null,
            'is_active' => false,
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }

    public function forGate(string $gateKey): static
    {
        return $this->state(fn (): array => [
            'gate_key' => $gateKey,
            'is_active' => true,
        ]);
    }
}
