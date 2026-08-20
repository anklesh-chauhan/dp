<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\CsvValidationProjectEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CsvValidationProjectEvent> */
class CsvValidationProjectEventFactory extends Factory
{
    protected $model = CsvValidationProjectEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'csv_validation_project_id' => CsvValidationProject::factory(),
            'from_status' => CsvValidationProjectStatus::Draft,
            'to_status' => CsvValidationProjectStatus::GxpAssessment,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
