<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class RiskAssessmentTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array{severity?: int, probability?: int, detectability?: int}|null  $residualScores
     * @param  array<string, mixed>  $context
     */
    public function transition(
        RiskAssessment $assessment,
        RiskAssessmentStatus $toStatus,
        User $actor,
        string $reason,
        ?string $mitigationPlan = null,
        ?array $residualScores = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): RiskAssessment {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this risk assessment transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every risk assessment transition.',
            ]);
        }

        return DB::transaction(function () use (
            $assessment,
            $toStatus,
            $actor,
            $normalizedReason,
            $mitigationPlan,
            $residualScores,
            $context,
            $ipAddress,
            $userAgent,
        ): RiskAssessment {
            $record = RiskAssessment::query()->lockForUpdate()->findOrFail($assessment->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Risk assessment cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $updates = $this->validatedUpdates(
                $record,
                $fromStatus,
                $toStatus,
                $actor,
                $mitigationPlan,
                $residualScores,
            );
            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $signatureHash = $this->requiresSignature($toStatus, $fromStatus)
                ? $this->electronicSignatureHasher->hashFor(
                    recordKey: $eventUuid,
                    meaning: $toStatus->value,
                    signerId: $actor->getKey(),
                    signedAt: $occurredAt,
                    reason: $normalizedReason,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                )
                : null;

            $record->update([
                'status' => $toStatus,
                ...$updates,
                ...($toStatus === RiskAssessmentStatus::Approved
                    ? ['approver_id' => $actor->getKey(), 'approved_at' => $occurredAt] : []),
                ...($toStatus === RiskAssessmentStatus::Monitoring && filled($record->mitigation_plan)
                    ? ['mitigation_completed_at' => $occurredAt] : []),
                ...($toStatus === RiskAssessmentStatus::Closed ? ['closed_at' => $occurredAt] : []),
            ]);
            $record->auditEvents()->create([
                'event_uuid' => $eventUuid,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $this->sanitize($context),
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $signatureHash === null ? null : $ipAddress,
                'signature_user_agent' => $signatureHash === null ? null : $userAgent,
                'occurred_at' => $occurredAt,
            ]);

            return $record->refresh();
        });
    }

    /** @return list<RiskAssessmentStatus> */
    private function allowedFrom(RiskAssessmentStatus $status): array
    {
        return match ($status) {
            RiskAssessmentStatus::Draft => [
                RiskAssessmentStatus::InReview,
                RiskAssessmentStatus::Cancelled,
            ],
            RiskAssessmentStatus::InReview => [
                RiskAssessmentStatus::Approved,
                RiskAssessmentStatus::Rejected,
                RiskAssessmentStatus::Draft,
            ],
            RiskAssessmentStatus::Approved => [
                RiskAssessmentStatus::MitigationInProgress,
                RiskAssessmentStatus::Monitoring,
            ],
            RiskAssessmentStatus::MitigationInProgress => [
                RiskAssessmentStatus::Monitoring,
                RiskAssessmentStatus::Cancelled,
            ],
            RiskAssessmentStatus::Monitoring => [
                RiskAssessmentStatus::Closed,
                RiskAssessmentStatus::MitigationInProgress,
            ],
            RiskAssessmentStatus::Closed,
            RiskAssessmentStatus::Rejected,
            RiskAssessmentStatus::Cancelled => [],
        };
    }

    private function permissionFor(RiskAssessmentStatus $status): string
    {
        return match ($status) {
            RiskAssessmentStatus::InReview,
            RiskAssessmentStatus::Draft => 'Review:RiskAssessment',
            RiskAssessmentStatus::Approved,
            RiskAssessmentStatus::Rejected => 'Approve:RiskAssessment',
            RiskAssessmentStatus::MitigationInProgress => 'Mitigate:RiskAssessment',
            RiskAssessmentStatus::Monitoring => 'Monitor:RiskAssessment',
            RiskAssessmentStatus::Closed => 'Close:RiskAssessment',
            RiskAssessmentStatus::Cancelled => 'Manage:RiskAssessment',
        };
    }

    /**
     * @param  array{severity?: int, probability?: int, detectability?: int}|null  $residualScores
     * @return array<string, mixed>
     */
    private function validatedUpdates(
        RiskAssessment $assessment,
        RiskAssessmentStatus $fromStatus,
        RiskAssessmentStatus $toStatus,
        User $actor,
        ?string $mitigationPlan,
        ?array $residualScores,
    ): array {
        if ($toStatus === RiskAssessmentStatus::InReview) {
            $this->validateScores([
                'severity' => $assessment->initial_severity,
                'probability' => $assessment->initial_probability,
                'detectability' => $assessment->initial_detectability,
            ], 'initial');
        }

        if (in_array($toStatus, [RiskAssessmentStatus::Approved, RiskAssessmentStatus::Rejected], true)
            && in_array((int) $actor->getKey(), array_filter([
                $assessment->created_by,
                $assessment->owner_id,
            ]), true)) {
            throw ValidationException::withMessages([
                'approver_id' => 'The creator or owner cannot independently approve or reject the assessment.',
            ]);
        }

        if ($toStatus === RiskAssessmentStatus::MitigationInProgress) {
            $normalizedPlan = trim((string) ($mitigationPlan ?? $assessment->mitigation_plan));
            if ($normalizedPlan === '' || $assessment->mitigation_due_at === null) {
                throw ValidationException::withMessages([
                    'mitigation_plan' => 'A mitigation plan and due date are required.',
                ]);
            }

            return [
                'mitigation_plan' => $normalizedPlan,
                ...($fromStatus === RiskAssessmentStatus::Monitoring ? [
                    'mitigation_completed_at' => null,
                    'residual_severity' => null,
                    'residual_probability' => null,
                    'residual_detectability' => null,
                ] : []),
            ];
        }

        if ($toStatus === RiskAssessmentStatus::Monitoring) {
            $scores = $residualScores ?? [
                'severity' => $assessment->residual_severity,
                'probability' => $assessment->residual_probability,
                'detectability' => $assessment->residual_detectability,
            ];
            $this->validateScores($scores, 'residual');

            return [
                'residual_severity' => $scores['severity'],
                'residual_probability' => $scores['probability'],
                'residual_detectability' => $scores['detectability'],
            ];
        }

        if ($toStatus === RiskAssessmentStatus::Closed) {
            $residualRpn = $assessment->residualRiskPriorityNumber();
            if ($residualRpn === null || $assessment->review_due_at === null) {
                throw ValidationException::withMessages([
                    'residual_severity' => 'Complete residual scoring and a review due date are required before closure.',
                ]);
            }

            if ($residualRpn > $assessment->initialRiskPriorityNumber()) {
                throw ValidationException::withMessages([
                    'residual_severity' => 'Residual risk cannot exceed the documented initial risk at closure.',
                ]);
            }
        }

        return [];
    }

    /** @param array{severity?: int, probability?: int, detectability?: int} $scores */
    private function validateScores(array $scores, string $prefix): void
    {
        foreach (['severity', 'probability', 'detectability'] as $factor) {
            $value = $scores[$factor] ?? null;
            if (! is_int($value) || $value < 1 || $value > 5) {
                throw ValidationException::withMessages([
                    "{$prefix}_{$factor}" => 'Risk scores must be whole numbers from 1 to 5.',
                ]);
            }
        }
    }

    private function requiresSignature(
        RiskAssessmentStatus $toStatus,
        RiskAssessmentStatus $fromStatus,
    ): bool {
        return in_array($toStatus, [
            RiskAssessmentStatus::Approved,
            RiskAssessmentStatus::Rejected,
            RiskAssessmentStatus::Monitoring,
            RiskAssessmentStatus::Closed,
            RiskAssessmentStatus::Cancelled,
        ], true) || ($fromStatus === RiskAssessmentStatus::Monitoring
            && $toStatus === RiskAssessmentStatus::MitigationInProgress);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        unset($context['signature'], $context['payload']);

        return $context;
    }
}
