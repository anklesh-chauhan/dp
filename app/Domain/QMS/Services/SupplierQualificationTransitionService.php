<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Enums\SupplierRiskLevel;
use App\Domain\QMS\Models\SupplierQualification;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SupplierQualificationTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(
        SupplierQualification $qualification,
        SupplierQualificationStatus $toStatus,
        User $actor,
        string $reason,
        ?string $qualificationRationale = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): SupplierQualification {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this supplier qualification transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every supplier qualification transition.',
            ]);
        }

        return DB::transaction(function () use (
            $qualification,
            $toStatus,
            $actor,
            $normalizedReason,
            $qualificationRationale,
            $context,
            $ipAddress,
            $userAgent,
        ): SupplierQualification {
            $record = SupplierQualification::query()->lockForUpdate()->findOrFail($qualification->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Supplier qualification cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $updates = $this->validatedUpdates(
                $record,
                $fromStatus,
                $toStatus,
                $actor,
                $qualificationRationale,
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
                ...($toStatus === SupplierQualificationStatus::UnderAssessment
                    && $record->qualification_started_at === null
                    ? ['qualification_started_at' => $occurredAt] : []),
                ...(in_array($toStatus, [
                    SupplierQualificationStatus::Qualified,
                    SupplierQualificationStatus::ConditionallyQualified,
                ], true) ? [
                    'approved_by' => $actor->getKey(),
                    'qualified_at' => $occurredAt,
                    'suspended_at' => null,
                ] : []),
                ...($toStatus === SupplierQualificationStatus::Suspended
                    ? ['suspended_at' => $occurredAt] : []),
                ...($toStatus === SupplierQualificationStatus::Disqualified
                    ? ['disqualified_at' => $occurredAt] : []),
                ...($fromStatus === SupplierQualificationStatus::Suspended
                    && $toStatus === SupplierQualificationStatus::UnderAssessment
                    ? ['suspended_at' => null] : []),
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

    /** @return list<SupplierQualificationStatus> */
    private function allowedFrom(SupplierQualificationStatus $status): array
    {
        return match ($status) {
            SupplierQualificationStatus::Draft => [
                SupplierQualificationStatus::UnderAssessment,
                SupplierQualificationStatus::Cancelled,
            ],
            SupplierQualificationStatus::UnderAssessment => [
                SupplierQualificationStatus::AuditRequired,
                SupplierQualificationStatus::Qualified,
                SupplierQualificationStatus::ConditionallyQualified,
                SupplierQualificationStatus::Disqualified,
                SupplierQualificationStatus::Cancelled,
            ],
            SupplierQualificationStatus::AuditRequired => [
                SupplierQualificationStatus::UnderAssessment,
                SupplierQualificationStatus::Qualified,
                SupplierQualificationStatus::ConditionallyQualified,
                SupplierQualificationStatus::Disqualified,
            ],
            SupplierQualificationStatus::Qualified,
            SupplierQualificationStatus::ConditionallyQualified => [
                SupplierQualificationStatus::UnderAssessment,
                SupplierQualificationStatus::Suspended,
                SupplierQualificationStatus::Disqualified,
                SupplierQualificationStatus::Expired,
            ],
            SupplierQualificationStatus::Suspended => [
                SupplierQualificationStatus::UnderAssessment,
                SupplierQualificationStatus::Disqualified,
            ],
            SupplierQualificationStatus::Expired => [
                SupplierQualificationStatus::UnderAssessment,
                SupplierQualificationStatus::Disqualified,
            ],
            SupplierQualificationStatus::Disqualified,
            SupplierQualificationStatus::Cancelled => [],
        };
    }

    private function permissionFor(SupplierQualificationStatus $status): string
    {
        return match ($status) {
            SupplierQualificationStatus::UnderAssessment => 'Assess:SupplierQualification',
            SupplierQualificationStatus::AuditRequired => 'Audit:SupplierQualification',
            SupplierQualificationStatus::Qualified,
            SupplierQualificationStatus::ConditionallyQualified => 'Approve:SupplierQualification',
            SupplierQualificationStatus::Suspended => 'Suspend:SupplierQualification',
            SupplierQualificationStatus::Disqualified => 'Disqualify:SupplierQualification',
            SupplierQualificationStatus::Expired => 'Review:SupplierQualification',
            SupplierQualificationStatus::Cancelled => 'Manage:SupplierQualification',
            SupplierQualificationStatus::Draft => 'Update:SupplierQualification',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUpdates(
        SupplierQualification $qualification,
        SupplierQualificationStatus $fromStatus,
        SupplierQualificationStatus $toStatus,
        User $actor,
        ?string $qualificationRationale,
    ): array {
        if ($toStatus === SupplierQualificationStatus::AuditRequired
            && $qualification->audit_due_at === null) {
            throw ValidationException::withMessages([
                'audit_due_at' => 'An audit due date is required when a supplier audit is mandated.',
            ]);
        }

        if (in_array($toStatus, [
            SupplierQualificationStatus::Qualified,
            SupplierQualificationStatus::ConditionallyQualified,
            SupplierQualificationStatus::Disqualified,
        ], true) && in_array((int) $actor->getKey(), array_filter([
            $qualification->created_by,
            $qualification->owner_id,
        ]), true)) {
            throw ValidationException::withMessages([
                'approved_by' => 'The creator or owner cannot make the independent qualification decision.',
            ]);
        }

        if (in_array($toStatus, [
            SupplierQualificationStatus::Qualified,
            SupplierQualificationStatus::ConditionallyQualified,
        ], true)) {
            $rationale = trim((string) ($qualificationRationale ?? $qualification->qualification_rationale));
            if ($rationale === '') {
                throw ValidationException::withMessages([
                    'qualification_rationale' => 'Qualification rationale is required.',
                ]);
            }

            if ($qualification->qualification_expires_at === null
                || $qualification->next_review_at === null
                || $qualification->next_review_at->gt($qualification->qualification_expires_at)) {
                throw ValidationException::withMessages([
                    'next_review_at' => 'Review and expiry dates are required, and review cannot follow expiry.',
                ]);
            }

            if (in_array($qualification->risk_level, [
                SupplierRiskLevel::High,
                SupplierRiskLevel::Critical,
            ], true) && $qualification->audit_completed_at === null) {
                throw ValidationException::withMessages([
                    'audit_completed_at' => 'High- and critical-risk suppliers require completed audit evidence.',
                ]);
            }

            return ['qualification_rationale' => $rationale];
        }

        if ($toStatus === SupplierQualificationStatus::Expired
            && ($qualification->qualification_expires_at === null
                || $qualification->qualification_expires_at->isFuture())) {
            throw ValidationException::withMessages([
                'qualification_expires_at' => 'Only an elapsed qualification may be marked expired.',
            ]);
        }

        if ($fromStatus === SupplierQualificationStatus::Suspended
            && $toStatus === SupplierQualificationStatus::UnderAssessment
            && trim((string) $qualificationRationale) === '') {
            throw ValidationException::withMessages([
                'qualification_rationale' => 'Reinstatement assessment requires documented rationale.',
            ]);
        }

        return [];
    }

    private function requiresSignature(
        SupplierQualificationStatus $toStatus,
        SupplierQualificationStatus $fromStatus,
    ): bool {
        return in_array($toStatus, [
            SupplierQualificationStatus::Qualified,
            SupplierQualificationStatus::ConditionallyQualified,
            SupplierQualificationStatus::Suspended,
            SupplierQualificationStatus::Disqualified,
            SupplierQualificationStatus::Expired,
            SupplierQualificationStatus::Cancelled,
        ], true) || ($fromStatus === SupplierQualificationStatus::Suspended
            && $toStatus === SupplierQualificationStatus::UnderAssessment);
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
