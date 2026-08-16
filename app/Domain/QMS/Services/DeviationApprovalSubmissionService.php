<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Enums\ProductModule;
use App\Exceptions\WorkflowException;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class DeviationApprovalSubmissionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly QualityApprovalWorkflowSelector $workflowSelector,
        private readonly QualityApprovalInstancePersistence $approvalPersistence,
        private readonly DeviationSubmissionAuthorization $submissionAuthorization,
        private readonly DeviationTransitionService $transitionService,
        private readonly QualityWorkflowNotificationService $workflowNotifications,
    ) {}

    public function submit(
        Deviation $deviation,
        User $submitter,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Deviation {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $this->submissionAuthorization->canSubmit($deviation, $submitter)) {
            throw new AuthorizationException('You are not authorized to submit this deviation.');
        }

        $workflow = $this->workflowSelector->selectFor($deviation);

        if ($workflow !== null && ! $workflow instanceof QualityApprovalWorkflow) {
            throw new WorkflowException(message: 'The selected quality workflow is invalid.');
        }

        if ($workflow !== null && $workflow->steps()->doesntExist()) {
            throw new WorkflowException(message: 'The selected quality workflow has no approval steps.');
        }

        return DB::transaction(function () use ($deviation, $submitter, $reason, $ipAddress, $userAgent, $workflow): Deviation {
            if ($workflow !== null) {
                $this->approvalPersistence->initializeFor($deviation, $workflow);
            }

            $submitted = $this->transitionService->transition(
                $deviation,
                DeviationStatus::Open,
                $submitter,
                $reason,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            $this->workflowNotifications->notifyDeviationSubmitted($submitted, $submitter);

            return $submitted;
        });
    }
}
