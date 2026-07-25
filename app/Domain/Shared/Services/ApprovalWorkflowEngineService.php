<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalDecisionAuthorization;
use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalDecisionPersistence;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Exceptions\WorkflowException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowEngineService
{
    public function __construct(
        private readonly ApprovalWorkflowDefinitionSelector $workflowDefinitionSelector,
        private readonly ApprovalInstancePersistence $approvalInstancePersistence,
        private readonly ApprovalSubmissionAuthorization $approvalSubmissionAuthorization,
        private readonly ApprovalSubmissionLifecycle $approvalSubmissionLifecycle,
        private readonly ApprovalDecisionAuthorization $approvalDecisionAuthorization,
        private readonly ApprovalDecisionOutcome $approvalDecisionOutcome,
        private readonly ApprovalDecisionPersistence $approvalDecisionPersistence,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
        private readonly Request $request,
    ) {}

    public function start(ApprovableSubject $subject, User $submitter, ?ApprovalWorkflowDefinition $workflow = null): void
    {
        $this->approvalSubmissionLifecycle->assertSubmittable($subject);

        if (! $this->canSubmit($subject, $submitter)) {
            throw new WorkflowException(
                message: 'You are not authorized to submit this document for approval.'
            );
        }

        $workflow ??= $this->resolveWorkflow($subject);

        if ($workflow === null) {
            throw new WorkflowException(
                message: 'No active approval workflow is configured for this department.'
            );
        }

        DB::transaction(function () use ($subject, $workflow, $submitter): void {
            $this->approvalSubmissionLifecycle->prepareSubmission($subject, $submitter);
            $this->approvalInstancePersistence->initializeFor($subject, $workflow);
            $this->approvalSubmissionLifecycle->markSubmitted($subject, $workflow, $submitter);
        });
    }

    public function approve(ApprovalInstance $approval, User $approver, ?string $comments = null): ApprovalInstance
    {
        $this->approvalDecisionAuthorization->authorizeDecision($approval, $approver);

        return $this->decide($approval, $approver, ApprovalDecisionCode::APPROVED, $comments);
    }

    public function reject(ApprovalInstance $approval, User $approver, ?string $comments = null): ApprovalInstance
    {
        $this->approvalDecisionAuthorization->authorizeDecision($approval, $approver);

        return $this->decide(
            $approval,
            $approver,
            ApprovalDecisionCode::REJECTED,
            $comments,
        );
    }

    public function return(ApprovalInstance $approval, User $approver, ?string $comments = null): ApprovalInstance
    {
        $this->approvalDecisionAuthorization->authorizeDecision($approval, $approver);

        return $this->decide($approval, $approver, ApprovalDecisionCode::RETURNED, $comments);
    }

    public function resolveWorkflow(ApprovableSubject $subject): ?ApprovalWorkflowDefinition
    {
        return $this->workflowDefinitionSelector->selectFor($subject);
    }

    public function canSubmit(ApprovableSubject $subject, User $user): bool
    {
        return $this->approvalSubmissionAuthorization->canSubmit($subject, $user);
    }

    private function decide(ApprovalInstance $approval, User $approver, ApprovalDecisionCode $decisionCode, ?string $comments): ApprovalInstance
    {
        $decidedAt = now();
        $signatureIpAddress = $this->request->ip();
        $signatureUserAgent = $this->request->userAgent();
        $signatureHash = $this->electronicSignatureHasher->hashFor(
            recordKey: $approval->approvalInstanceKey(),
            meaning: $decisionCode->value,
            signerId: $approver->id,
            signedAt: $decidedAt,
            reason: $comments,
            ipAddress: $signatureIpAddress,
            userAgent: $signatureUserAgent,
        );

        return DB::transaction(function () use ($approval, $approver, $decisionCode, $comments, $decidedAt, $signatureHash, $signatureIpAddress, $signatureUserAgent): ApprovalInstance {
            $approval = $this->approvalDecisionPersistence->recordDecision(
                approval: $approval,
                decisionCode: $decisionCode->value,
                decidedById: $approver->id,
                comments: $comments,
                decidedAt: $decidedAt,
                signatureHash: $signatureHash,
                signatureIpAddress: $signatureIpAddress,
                signatureUserAgent: $signatureUserAgent,
            );

            return $this->approvalDecisionOutcome->applyOutcome(
                approval: $approval,
                decisionCode: $decisionCode->value,
                decidedBy: $approver,
            );
        });
    }
}
