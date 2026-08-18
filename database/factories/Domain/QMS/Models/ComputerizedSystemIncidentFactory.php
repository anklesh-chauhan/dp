<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ComputerizedSystemIncidentSeverity;
use App\Domain\QMS\Enums\ComputerizedSystemIncidentStatus;
use App\Domain\QMS\Models\ComputerizedSystemIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ComputerizedSystemIncident> */
final class ComputerizedSystemIncidentFactory extends Factory
{
    protected $model = ComputerizedSystemIncident::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(6),
            'status' => ComputerizedSystemIncidentStatus::Open,
            'severity' => ComputerizedSystemIncidentSeverity::Minor,
            'category' => 'availability',
            'description' => fake()->paragraph(),
            'impact' => 'GxP computerized system availability was interrupted.',
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
            'detected_at' => now(),
        ];
    }
}
