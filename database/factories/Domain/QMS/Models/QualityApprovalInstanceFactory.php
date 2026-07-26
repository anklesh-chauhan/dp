<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<QualityApprovalInstance> */
final class QualityApprovalInstanceFactory extends Factory
{
    protected $model = QualityApprovalInstance::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'instance_uuid' => (string) Str::uuid(),
            'submission_uuid' => (string) Str::uuid(),
            'subject_type' => Deviation::class,
            'subject_id' => Deviation::factory(),
            'workflow_step_id' => QualityApprovalWorkflowStep::factory(),
            'workflow_id' => fn (array $attributes): int => QualityApprovalWorkflowStep::query()
                ->findOrFail($attributes['workflow_step_id'])
                ->workflow_id,
            'decision_code' => 'pending',
        ];
    }
}
