<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InternalAuditTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /** @param array<string, mixed> $context */
    public function transition(
        InternalAudit $audit,
        InternalAuditStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): InternalAudit {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this internal audit transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required for every internal audit transition.']);
        }

        return DB::transaction(function () use ($audit, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): InternalAudit {
            $record = InternalAudit::query()->lockForUpdate()->findOrFail($audit->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Internal audit cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $this->validateMilestones($record, $toStatus);
            $this->validateFindingClosure($record, $toStatus);
            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $signatureHash = $this->requiresSignature($toStatus)
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
                ...($toStatus === InternalAuditStatus::InProgress && $record->started_at === null
                    ? ['started_at' => $occurredAt] : []),
                ...($toStatus === InternalAuditStatus::Reporting && $record->completed_at === null
                    ? ['completed_at' => $occurredAt] : []),
                ...($toStatus === InternalAuditStatus::Closed ? ['closed_at' => $occurredAt] : []),
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

    /** @return list<InternalAuditStatus> */
    private function allowedFrom(InternalAuditStatus $status): array
    {
        return match ($status) {
            InternalAuditStatus::Draft => [InternalAuditStatus::Scheduled, InternalAuditStatus::Cancelled],
            InternalAuditStatus::Scheduled => [InternalAuditStatus::InProgress, InternalAuditStatus::Cancelled],
            InternalAuditStatus::InProgress => [InternalAuditStatus::Reporting, InternalAuditStatus::Cancelled],
            InternalAuditStatus::Reporting => [InternalAuditStatus::FollowUp, InternalAuditStatus::Closed],
            InternalAuditStatus::FollowUp => [InternalAuditStatus::Closed],
            InternalAuditStatus::Closed, InternalAuditStatus::Cancelled => [],
        };
    }

    private function permissionFor(InternalAuditStatus $status): string
    {
        return match ($status) {
            InternalAuditStatus::Scheduled => 'Schedule:InternalAudit',
            InternalAuditStatus::InProgress => 'Conduct:InternalAudit',
            InternalAuditStatus::Reporting => 'Report:InternalAudit',
            InternalAuditStatus::FollowUp => 'FollowUp:InternalAudit',
            InternalAuditStatus::Closed => 'Close:InternalAudit',
            InternalAuditStatus::Cancelled => 'Manage:InternalAudit',
            InternalAuditStatus::Draft => 'Update:InternalAudit',
        };
    }

    private function validateMilestones(InternalAudit $audit, InternalAuditStatus $toStatus): void
    {
        if ($toStatus === InternalAuditStatus::Scheduled && (
            $audit->lead_auditor_id === null
            || $audit->scheduled_start_at === null
            || $audit->scheduled_end_at === null
            || $audit->scheduled_end_at->lt($audit->scheduled_start_at)
        )) {
            throw ValidationException::withMessages([
                'scheduled_start_at' => 'A valid schedule and lead auditor are required before scheduling.',
            ]);
        }

        if (in_array($toStatus, [InternalAuditStatus::FollowUp, InternalAuditStatus::Closed], true)
            && $audit->report_issued_at === null) {
            throw ValidationException::withMessages([
                'report_issued_at' => 'The audit report must be issued before follow-up or closure.',
            ]);
        }

        if ($toStatus === InternalAuditStatus::FollowUp && $audit->follow_up_due_at === null) {
            throw ValidationException::withMessages([
                'follow_up_due_at' => 'A follow-up due date is required.',
            ]);
        }
    }

    private function validateFindingClosure(InternalAudit $audit, InternalAuditStatus $toStatus): void
    {
        if ($toStatus !== InternalAuditStatus::Closed) {
            return;
        }

        if ($audit->findings()
            ->whereNotIn('disposition', [
                AuditFindingDisposition::Closed->value,
                AuditFindingDisposition::Rejected->value,
                AuditFindingDisposition::Cancelled->value,
            ])
            ->exists()) {
            throw ValidationException::withMessages([
                'findings' => 'Every audit finding must be closed, rejected, or cancelled before audit closure.',
            ]);
        }
    }

    private function requiresSignature(InternalAuditStatus $status): bool
    {
        return in_array($status, [
            InternalAuditStatus::Reporting,
            InternalAuditStatus::FollowUp,
            InternalAuditStatus::Closed,
            InternalAuditStatus::Cancelled,
        ], true);
    }

    /** @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function sanitize(array $context): array
    {
        unset($context['signature'], $context['payload']);

        return $context;
    }
}
