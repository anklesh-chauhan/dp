<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Models\ApprovalDecision;
use App\Models\SopApproval;
use App\Models\SopDocument;
use InvalidArgumentException;

class SopApprovalPersistenceAdapter implements ApprovalInstancePersistence
{
    public function initializeFor(
        ApprovableSubject $subject,
        ApprovalWorkflowDefinition $workflow,
    ): void {
        if (! $subject instanceof SopDocument) {
            throw new InvalidArgumentException(
                'The SOP approval persistence adapter requires a SopDocument subject.'
            );
        }

        $pendingDecisionId = ApprovalDecision::idFor(ApprovalDecision::PENDING);

        $subject->approvals()->update([
            'approval_decision_id' => $pendingDecisionId,
            'approved_by' => null,
            'comments' => null,
            'approved_at' => null,
            'signature_hash' => null,
            'signature_ip_address' => null,
            'signature_user_agent' => null,
        ]);

        foreach ($workflow->approvalWorkflowStepDefinitions() as $step) {
            SopApproval::query()->firstOrCreate([
                'document_id' => $subject->id,
                'workflow_step_id' => $step->approvalWorkflowStepDefinitionKey(),
            ], [
                'approval_decision_id' => $pendingDecisionId,
            ]);
        }
    }
}
