<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\ApprovableSubject;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalInstancePersistence;
use App\Domain\Shared\Contracts\ApprovalSubmissionAuthorization;
use App\Domain\Shared\Contracts\ApprovalSubmissionLifecycle;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinition;
use App\Domain\Shared\Contracts\ApprovalWorkflowDefinitionSelector;
use App\Exceptions\WorkflowException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowEngineService
{
    public function __construct(
        private readonly ApprovalWorkflowDefinitionSelector $workflowDefinitionSelector,
        private readonly ApprovalInstancePersistence $approvalInstancePersistence,
        private readonly ApprovalSubmissionAuthorization $approvalSubmissionAuthorization,
        private readonly ApprovalSubmissionLifecycle $approvalSubmissionLifecycle,
        private readonly ApprovalDecisionService $approvalDecisionService,
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
        return $this->approvalDecisionService->approve($approval, $approver, $comments);
    }

    public function reject(ApprovalInstance $approval, User $approver, ?string $comments = null): ApprovalInstance
    {
        return $this->approvalDecisionService->reject($approval, $approver, $comments);
    }

    public function return(ApprovalInstance $approval, User $approver, ?string $comments = null): ApprovalInstance
    {
        return $this->approvalDecisionService->return($approval, $approver, $comments);
    }

    public function resolveWorkflow(ApprovableSubject $subject): ?ApprovalWorkflowDefinition
    {
        return $this->workflowDefinitionSelector->selectFor($subject);
    }

    public function canSubmit(ApprovableSubject $subject, User $user): bool
    {
        return $this->approvalSubmissionAuthorization->canSubmit($subject, $user);
    }
}
