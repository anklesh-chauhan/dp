<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Enums\ProductModule;
use App\Exceptions\WorkflowException;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ChangeControlApprovalSubmissionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly QualityApprovalWorkflowSelector $workflowSelector,
        private readonly QualityApprovalInstancePersistence $approvalPersistence,
        private readonly ChangeControlSubmissionAuthorization $submissionAuthorization,
        private readonly ChangeControlTransitionService $transitionService,
        private readonly QualityWorkflowNotificationService $workflowNotifications,
    ) {}

    public function submit(
        ChangeControl $changeControl,
        User $submitter,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ChangeControl {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        $denialReason = $this->submissionAuthorization->denialReason($changeControl, $submitter);

        if ($denialReason !== null) {
            throw new AuthorizationException($denialReason);
        }

        $workflow = $this->workflowSelector->selectFor($changeControl);

        if ($workflow !== null && ! $workflow instanceof QualityApprovalWorkflow) {
            throw new WorkflowException(message: 'The selected quality workflow is invalid.');
        }

        if ($workflow !== null && $workflow->steps()->doesntExist()) {
            throw new WorkflowException(message: 'The selected quality workflow has no approval steps.');
        }

        return DB::transaction(function () use ($changeControl, $submitter, $reason, $ipAddress, $userAgent, $workflow): ChangeControl {
            if ($workflow !== null) {
                $this->approvalPersistence->initializeFor($changeControl, $workflow);
            }

            $submitted = $this->transitionService->transition(
                $changeControl,
                ChangeControlStatus::Submitted,
                $submitter,
                $reason,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            $this->workflowNotifications->notifyChangeControlSubmitted($submitted, $submitter);

            return $submitted;
        });
    }
}
