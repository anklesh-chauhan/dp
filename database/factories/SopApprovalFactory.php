<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ApprovalDecision;
use App\Models\SopApproval;
use App\Models\SopDocument;
use App\Models\SopWorkflowStep;
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
            'decision' => ApprovalDecision::Pending,
            'comments' => null,
            'approved_at' => null,
            'signature_hash' => null,
        ];
    }
}
