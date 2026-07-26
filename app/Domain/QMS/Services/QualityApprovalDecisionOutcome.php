<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\Shared\Contracts\ApprovalDecisionOutcome;
use App\Domain\Shared\Contracts\ApprovalInstance;
use App\Domain\Shared\Enums\ApprovalDecisionCode;
use App\Models\User;
use InvalidArgumentException;

final class QualityApprovalDecisionOutcome implements ApprovalDecisionOutcome
{
    public function __construct(
        private readonly DeviationTransitionService $deviationTransitionService,
    ) {}

    public function applyOutcome(
        ApprovalInstance $approval,
        string $decisionCode,
        User $decidedBy,
    ): ApprovalInstance {
        if (! $approval instanceof QualityApprovalInstance) {
            throw new InvalidArgumentException('Quality decision outcomes require a quality approval instance.');
        }

        $subject = $approval->approvalInstanceSubject();

        if (! $subject instanceof Deviation) {
            throw new InvalidArgumentException('Deviation approval outcomes require a Deviation subject.');
        }

        return match ($decisionCode) {
            ApprovalDecisionCode::APPROVED->value => $this->applyApprovedOutcome(
                $approval,
                $subject,
                $decidedBy,
            ),
            ApprovalDecisionCode::REJECTED->value => $this->applyTerminalOutcome(
                $approval,
                $subject,
                $decidedBy,
                DeviationStatus::Rejected,
            ),
            ApprovalDecisionCode::RETURNED->value => $this->applyTerminalOutcome(
                $approval,
                $subject,
                $decidedBy,
                DeviationStatus::Draft,
            ),
            default => throw new InvalidArgumentException(
                "Unsupported quality approval outcome '{$decisionCode}'.",
            ),
        };
    }

    private function applyApprovedOutcome(
        QualityApprovalInstance $approval,
        Deviation $deviation,
        User $decidedBy,
    ): QualityApprovalInstance {
        $instances = QualityApprovalInstance::query()
            ->where('submission_uuid', $approval->submission_uuid)
            ->with('workflowStep')
            ->get();
        $mandatoryInstances = $instances->filter(
            fn (QualityApprovalInstance $instance): bool => $instance->workflowStep->is_mandatory,
        );
        $requiredInstances = $mandatoryInstances->isEmpty() ? $instances : $mandatoryInstances;

        if ($requiredInstances->every(
            fn (QualityApprovalInstance $instance): bool => $instance->decision_code === ApprovalDecisionCode::APPROVED->value,
        )) {
            $this->markRemainingNotRequired($approval);
            $this->transitionDeviation($approval, $deviation, $decidedBy, DeviationStatus::UnderInvestigation);
        }

        return $approval;
    }

    private function applyTerminalOutcome(
        QualityApprovalInstance $approval,
        Deviation $deviation,
        User $decidedBy,
        DeviationStatus $status,
    ): QualityApprovalInstance {
        $this->markRemainingNotRequired($approval);
        $this->transitionDeviation($approval, $deviation, $decidedBy, $status);

        return $approval;
    }

    private function markRemainingNotRequired(QualityApprovalInstance $approval): void
    {
        QualityApprovalInstance::query()
            ->where('submission_uuid', $approval->submission_uuid)
            ->whereKeyNot($approval->getKey())
            ->where('decision_code', 'pending')
            ->update(['decision_code' => 'not_required']);
    }

    private function transitionDeviation(
        QualityApprovalInstance $approval,
        Deviation $deviation,
        User $decidedBy,
        DeviationStatus $status,
    ): void {
        $this->deviationTransitionService->transition(
            $deviation,
            $status,
            $decidedBy,
            $approval->comments,
            [
                'approval_instance_uuid' => $approval->instance_uuid,
                'approval_submission_uuid' => $approval->submission_uuid,
                'approval_decision' => $approval->decision_code,
            ],
            $approval->signature_ip_address,
            $approval->signature_user_agent,
        );
    }
}
