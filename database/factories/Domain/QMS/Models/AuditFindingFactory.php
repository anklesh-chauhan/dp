<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\AuditFindingSeverity;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\InternalAudit;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AuditFinding> */
final class AuditFindingFactory extends Factory
{
    protected $model = AuditFinding::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'internal_audit_id' => InternalAudit::factory(),
            'severity' => AuditFindingSeverity::Minor,
            'classification' => AuditFindingClassification::Observation,
            'disposition' => AuditFindingDisposition::Open,
            'clause_reference' => fake()->optional()->bothify('SOP-## §#.#'),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'objective_evidence' => fake()->paragraph(),
            'department_id' => Department::factory(),
            'owner_id' => User::factory(),
            'identified_by' => User::factory(),
            'identified_at' => now(),
            'response_due_at' => today()->addDays(30),
        ];
    }
}
