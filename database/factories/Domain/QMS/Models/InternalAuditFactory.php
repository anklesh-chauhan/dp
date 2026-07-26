<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Enums\InternalAuditType;
use App\Domain\QMS\Models\InternalAudit;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InternalAudit> */
final class InternalAuditFactory extends Factory
{
    protected $model = InternalAudit::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $scheduledStart = today()->addDays(fake()->numberBetween(7, 45));

        return [
            'type' => InternalAuditType::Internal,
            'status' => InternalAuditStatus::Draft,
            'title' => fake()->sentence(5),
            'scope' => fake()->paragraph(),
            'objectives' => fake()->sentence(),
            'criteria' => 'Applicable procedures and quality-system requirements.',
            'department_id' => Department::factory(),
            'lead_auditor_id' => User::factory(),
            'created_by' => User::factory(),
            'owner_id' => User::factory(),
            'scheduled_start_at' => $scheduledStart,
            'scheduled_end_at' => $scheduledStart->copy()->addDays(2),
            'follow_up_due_at' => $scheduledStart->copy()->addDays(30),
        ];
    }
}
