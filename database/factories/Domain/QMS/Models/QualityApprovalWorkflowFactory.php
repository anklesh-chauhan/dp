<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<QualityApprovalWorkflow> */
final class QualityApprovalWorkflowFactory extends Factory
{
    protected $model = QualityApprovalWorkflow::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'workflow_code' => 'QWF-'.Str::upper(Str::random(10)),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->paragraph(),
            'subject_type' => Deviation::class,
            'department_id' => null,
            'is_active' => true,
        ];
    }
}
