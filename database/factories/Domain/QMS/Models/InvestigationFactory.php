<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\Investigation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Investigation>
 */
class InvestigationFactory extends Factory
{
    protected $model = Investigation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deviation_id' => Deviation::factory(),
            'status' => InvestigationStatus::Draft,
            'lead_id' => User::factory(),
            'methodology' => 'Structured root cause analysis using the 5 Whys method.',
            'root_cause' => null,
            'conclusion' => null,
            'started_at' => null,
            'due_at' => today()->addDays(30),
            'completed_at' => null,
        ];
    }
}
