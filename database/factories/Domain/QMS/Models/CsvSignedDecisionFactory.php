<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Models\CsvRequirement;
use App\Domain\QMS\Models\CsvSignedDecision;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CsvSignedDecision> */
final class CsvSignedDecisionFactory extends Factory
{
    protected $model = CsvSignedDecision::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'decision_uuid' => (string) Str::uuid(),
            'csv_validation_project_id' => CsvValidationProject::factory(),
            'subject_type' => CsvRequirement::class,
            'subject_id' => CsvRequirement::factory(),
            'decision_code' => 'approved',
            'from_state' => CsvRequirementStatus::Draft->value,
            'to_state' => CsvRequirementStatus::Approved->value,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => null,
            'signature_hash' => hash('sha256', (string) Str::uuid()),
            'signature_ip_address' => '127.0.0.1',
            'signature_user_agent' => 'QualiGxP-Factory/1.0',
            'occurred_at' => now(),
        ];
    }
}
