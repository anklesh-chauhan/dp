<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ApprovalDecision;
use App\Models\SopApproval;
use App\Models\SopDocument;
use App\Models\SopWorkflowStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopApproval>
 */
class SopApprovalFactory extends Factory
{
    protected $model = SopApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => SopDocument::factory(),
            'workflow_step_id' => SopWorkflowStep::factory(),
            'approved_by' => User::factory(),
            'approval_decision_id' => ApprovalDecision::idFor(ApprovalDecision::PENDING),
            'comments' => fake()->optional()->sentence(),
            'approved_at' => now(),
            'signature_hash' => hash('sha256', fake()->uuid()),
        ];
    }
}
