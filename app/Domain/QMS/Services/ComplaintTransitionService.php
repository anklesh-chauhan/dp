<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Models\Complaint;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ComplaintTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function transition(
        Complaint $complaint,
        ComplaintStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Complaint {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this complaint transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every complaint transition.',
            ]);
        }

        return DB::transaction(function () use ($complaint, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): Complaint {
            $record = Complaint::query()->lockForUpdate()->findOrFail($complaint->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Complaint cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $this->validateRegulatoryAssessment($record, $toStatus);

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
                ...($toStatus === ComplaintStatus::UnderAssessment && $record->acknowledged_at === null
                    ? ['acknowledged_at' => $occurredAt]
                    : []),
                ...($toStatus === ComplaintStatus::Closed ? ['closed_at' => $occurredAt] : []),
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

    /** @return list<ComplaintStatus> */
    private function allowedFrom(ComplaintStatus $status): array
    {
        return match ($status) {
            ComplaintStatus::Draft => [
                ComplaintStatus::Received,
                ComplaintStatus::Cancelled,
            ],
            ComplaintStatus::Received => [
                ComplaintStatus::UnderAssessment,
                ComplaintStatus::Rejected,
                ComplaintStatus::Cancelled,
            ],
            ComplaintStatus::UnderAssessment => [
                ComplaintStatus::UnderInvestigation,
                ComplaintStatus::ResponsePending,
                ComplaintStatus::Rejected,
                ComplaintStatus::Cancelled,
            ],
            ComplaintStatus::UnderInvestigation => [
                ComplaintStatus::ResponsePending,
                ComplaintStatus::Rejected,
                ComplaintStatus::Cancelled,
            ],
            ComplaintStatus::ResponsePending => [
                ComplaintStatus::Closed,
                ComplaintStatus::UnderInvestigation,
                ComplaintStatus::Cancelled,
            ],
            ComplaintStatus::Closed,
            ComplaintStatus::Rejected,
            ComplaintStatus::Cancelled => [],
        };
    }

    private function permissionFor(ComplaintStatus $status): string
    {
        return match ($status) {
            ComplaintStatus::Received,
            ComplaintStatus::UnderAssessment,
            ComplaintStatus::Rejected => 'Assess:Complaint',
            ComplaintStatus::UnderInvestigation => 'Investigate:Complaint',
            ComplaintStatus::ResponsePending => 'Respond:Complaint',
            ComplaintStatus::Closed => 'Close:Complaint',
            ComplaintStatus::Cancelled => 'Manage:Complaint',
            ComplaintStatus::Draft => 'Update:Complaint',
        };
    }

    /**
     * @throws ValidationException
     */
    private function validateRegulatoryAssessment(Complaint $complaint, ComplaintStatus $toStatus): void
    {
        if (
            in_array($toStatus, [ComplaintStatus::ResponsePending, ComplaintStatus::Closed], true)
            && ($complaint->adverse_event_suspected === null || $complaint->regulatory_reportable === null)
        ) {
            throw ValidationException::withMessages([
                'regulatory_reportable' => 'Safety and regulatory reportability must be assessed before response or closure.',
            ]);
        }

        if (
            $toStatus === ComplaintStatus::Closed
            && $complaint->regulatory_reportable
            && ($complaint->regulatory_reported_at === null || blank($complaint->regulatory_reference))
        ) {
            throw ValidationException::withMessages([
                'regulatory_reported_at' => 'A reportable complaint requires reporting evidence before closure.',
            ]);
        }
    }

    private function requiresSignature(ComplaintStatus $status): bool
    {
        return in_array($status, [
            ComplaintStatus::ResponsePending,
            ComplaintStatus::Closed,
            ComplaintStatus::Rejected,
            ComplaintStatus::Cancelled,
        ], true);
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
