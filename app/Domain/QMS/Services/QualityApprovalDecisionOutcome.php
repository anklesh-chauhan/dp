<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\ChangeControl;
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
        private readonly ChangeControlTransitionService $changeControlTransitionService,
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

        return match (true) {
            $subject instanceof Deviation => $this->applyDeviationOutcome(
                $approval,
                $subject,
                $decisionCode,
                $decidedBy,
            ),
            $subject instanceof ChangeControl => $this->applyChangeControlOutcome(
                $approval,
                $subject,
                $decisionCode,
                $decidedBy,
            ),
            default => throw new InvalidArgumentException('Quality approval outcomes require a Deviation or Change Control subject.'),
        };
    }

    private function applyDeviationOutcome(
        QualityApprovalInstance $approval,
        Deviation $deviation,
        string $decisionCode,
        User $decidedBy,
    ): QualityApprovalInstance {
        return match ($decisionCode) {
            ApprovalDecisionCode::APPROVED->value => $this->applyApprovedDeviationOutcome(
                $approval,
                $deviation,
                $decidedBy,
            ),
            ApprovalDecisionCode::REJECTED->value => $this->applyTerminalDeviationOutcome(
                $approval,
                $deviation,
                $decidedBy,
                DeviationStatus::Rejected,
            ),
            ApprovalDecisionCode::RETURNED->value => $this->applyTerminalDeviationOutcome(
                $approval,
                $deviation,
                $decidedBy,
                DeviationStatus::Draft,
            ),
            default => throw new InvalidArgumentException(
                "Unsupported quality approval outcome '{$decisionCode}'.",
            ),
        };
    }

    private function applyChangeControlOutcome(
        QualityApprovalInstance $approval,
        ChangeControl $changeControl,
        string $decisionCode,
        User $decidedBy,
    ): QualityApprovalInstance {
        return match ($decisionCode) {
            ApprovalDecisionCode::APPROVED->value => $this->applyApprovedChangeControlOutcome(
                $approval,
                $changeControl,
                $decidedBy,
            ),
            ApprovalDecisionCode::REJECTED->value => $this->applyTerminalChangeControlOutcome(
                $approval,
                $changeControl,
                $decidedBy,
                ChangeControlStatus::Rejected,
            ),
            ApprovalDecisionCode::RETURNED->value => $this->applyTerminalChangeControlOutcome(
                $approval,
                $changeControl,
                $decidedBy,
                ChangeControlStatus::Draft,
            ),
            default => throw new InvalidArgumentException(
                "Unsupported quality approval outcome '{$decisionCode}'.",
            ),
        };
    }

    private function applyApprovedDeviationOutcome(
        QualityApprovalInstance $approval,
        Deviation $deviation,
        User $decidedBy,
    ): QualityApprovalInstance {
        if ($this->requiredStepsAreApproved($approval)) {
            $this->markRemainingNotRequired($approval);
            $this->transitionDeviation($approval, $deviation, $decidedBy, DeviationStatus::UnderInvestigation);
        }

        return $approval;
    }

    private function applyApprovedChangeControlOutcome(
        QualityApprovalInstance $approval,
        ChangeControl $changeControl,
        User $decidedBy,
    ): QualityApprovalInstance {
        if ($this->requiredStepsAreApproved($approval)) {
            $this->markRemainingNotRequired($approval);
            $this->transitionChangeControl($approval, $changeControl, $decidedBy, ChangeControlStatus::Approved);
        }

        return $approval;
    }

    private function applyTerminalDeviationOutcome(
        QualityApprovalInstance $approval,
        Deviation $deviation,
        User $decidedBy,
        DeviationStatus $status,
    ): QualityApprovalInstance {
        $this->markRemainingNotRequired($approval);
        $this->transitionDeviation($approval, $deviation, $decidedBy, $status);

        return $approval;
    }

    private function applyTerminalChangeControlOutcome(
        QualityApprovalInstance $approval,
        ChangeControl $changeControl,
        User $decidedBy,
        ChangeControlStatus $status,
    ): QualityApprovalInstance {
        $this->markRemainingNotRequired($approval);
        $this->transitionChangeControl($approval, $changeControl, $decidedBy, $status);

        return $approval;
    }

    private function requiredStepsAreApproved(QualityApprovalInstance $approval): bool
    {
        $instances = QualityApprovalInstance::query()
            ->where('submission_uuid', $approval->submission_uuid)
            ->with('workflowStep')
            ->get();
        $mandatoryInstances = $instances->filter(
            fn (QualityApprovalInstance $instance): bool => $instance->workflowStep->is_mandatory,
        );
        $requiredInstances = $mandatoryInstances->isEmpty() ? $instances : $mandatoryInstances;

        return $requiredInstances->every(
            fn (QualityApprovalInstance $instance): bool => $instance->decision_code === ApprovalDecisionCode::APPROVED->value,
        );
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
            $this->approvalContext($approval),
            $approval->signature_ip_address,
            $approval->signature_user_agent,
        );
    }

    private function transitionChangeControl(
        QualityApprovalInstance $approval,
        ChangeControl $changeControl,
        User $decidedBy,
        ChangeControlStatus $status,
    ): void {
        $this->changeControlTransitionService->transition(
            $changeControl,
            $status,
            $decidedBy,
            $approval->comments,
            $this->approvalContext($approval),
            $approval->signature_ip_address,
            $approval->signature_user_agent,
        );
    }

    /** @return array<string, mixed> */
    private function approvalContext(QualityApprovalInstance $approval): array
    {
        return [
            'approval_instance_uuid' => $approval->instance_uuid,
            'approval_submission_uuid' => $approval->submission_uuid,
            'approval_decision' => $approval->decision_code,
        ];
    }
}
