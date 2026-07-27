<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SopTemplateApprovalInstance;
use App\Models\SopTemplateVersion;
use App\Models\SopWorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SopTemplateApprovalInstance>
 */
class SopTemplateApprovalInstanceFactory extends Factory
{
    protected $model = SopTemplateApprovalInstance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'instance_uuid' => (string) Str::uuid(),
            'submission_uuid' => (string) Str::uuid(),
            'sop_template_version_id' => SopTemplateVersion::factory(),
            'workflow_step_id' => SopWorkflowStep::factory(),
            'workflow_id' => fn (array $attributes): int => (int) SopWorkflowStep::query()
                ->whereKey($attributes['workflow_step_id'])
                ->value('workflow_id'),
            'decision_code' => 'pending',
        ];
    }
}
