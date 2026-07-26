<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/** @extends Factory<QualityApprovalWorkflowStep> */
final class QualityApprovalWorkflowStepFactory extends Factory
{
    protected $model = QualityApprovalWorkflowStep::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workflow_id' => QualityApprovalWorkflow::factory(),
            'step_no' => 1,
            'role_id' => Role::findOrCreate('quality reviewer', 'web')->id,
            'department_id' => null,
            'is_mandatory' => true,
        ];
    }
}
