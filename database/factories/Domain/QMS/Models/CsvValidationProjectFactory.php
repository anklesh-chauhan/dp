<?php

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Domain\QMS\Models\CsvValidationProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CsvValidationProject>
 */
class CsvValidationProjectFactory extends Factory
{
    protected $model = CsvValidationProject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'system_identifier' => fake()->unique()->bothify('SYS-###'),
            'system_name' => fake()->words(3, true),
            'system_version' => fake()->numerify('#.#.#'),
            'intended_use' => fake()->sentence(),
            'status' => CsvValidationProjectStatus::Draft,
            'gxp_criticality' => CsvCriticality::High,
            'is_gxp' => true,
            'uses_electronic_records' => true,
            'uses_electronic_signatures' => true,
            'regulatory_scope' => ['21 CFR Part 11', 'EU GMP Annex 11'],
        ];
    }
}
