<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Enums\LaboratoryOosType;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LaboratoryOosEvent> */
final class LaboratoryOosEventFactory extends Factory
{
    protected $model = LaboratoryOosEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'type' => LaboratoryOosType::Oos,
            'status' => LaboratoryOosStatus::Draft,
            'title' => fake()->sentence(5),
            'test_name' => fake()->words(3, true),
            'method_reference' => fake()->optional()->bothify('STP-####'),
            'sample_id' => fake()->optional()->bothify('SMP-####'),
            'batch_number' => fake()->bothify('BATCH-####'),
            'product_name' => fake()->words(3, true),
            'specification_limit' => fake()->optional()->numerify('NMT #.##%'),
            'observed_result' => fake()->optional()->numerify('#.##'),
            'unit' => fake()->optional()->randomElement(['%', 'mg/mL', 'IU/mL']),
            'phase_one_outcome' => LaboratoryOosPhaseOutcome::Pending,
            'department_id' => Department::factory(),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
            'analyst_id' => User::factory(),
        ];
    }
}
