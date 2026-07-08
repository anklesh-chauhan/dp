<?php

namespace Database\Factories;

use App\Models\DocumentType;
use App\Models\RegulationTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegulationTag>
 */
class RegulationTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'code' => 'RT-'.fake()->unique()->bothify('??-####'),
            'description' => fake()->paragraph(),
            'color' => fake()->color(),
            'icon' => fake()->word(),
            'document_types' => DocumentType::factory(),
        ];
    }
}
