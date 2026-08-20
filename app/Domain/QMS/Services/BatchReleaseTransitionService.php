<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\BatchReleaseStatus;
use App\Domain\QMS\Models\BatchRelease;
use App\Domain\Shared\Services\ContentBoundElectronicSignatureIssuer;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BatchReleaseTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ContentBoundElectronicSignatureIssuer $contentBoundSignatures,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(
        BatchRelease $release,
        BatchReleaseStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): BatchRelease {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this batch release transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every batch release transition.',
            ]);
        }

        return DB::transaction(function () use ($release, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): BatchRelease {
            $record = BatchRelease::query()->lockForUpdate()->findOrFail($release->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Batch release cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            if (in_array($toStatus, [BatchReleaseStatus::Released, BatchReleaseStatus::Rejected], true)
                && $actor->is($record->creator)) {
                throw ValidationException::withMessages([
                    'status' => 'The independent quality releaser must be different from the record creator.',
                ]);
            }

            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $eventContext = $this->sanitize($context);
            $signatureHash = null;
            if ($this->requiresSignature($toStatus)) {
                [$signatureHash, $eventContext] = $this->contentBoundSignatures->issue(
                    signer: $actor,
                    subject: $record,
                    recordKey: $eventUuid,
                    meaning: $toStatus->value,
                    signedAt: $occurredAt,
                    reason: $normalizedReason,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                    context: $eventContext,
                );
            }

            $milestones = match ($toStatus) {
                BatchReleaseStatus::Released => [
                    'released_at' => $occurredAt,
                    'released_by' => $actor->getKey(),
                    'disposition_rationale' => $normalizedReason,
                ],
                BatchReleaseStatus::Rejected => [
                    'rejected_at' => $occurredAt,
                    'disposition_rationale' => $normalizedReason,
                ],
                default => [],
            };

            $record->update([
                'status' => $toStatus,
                ...$milestones,
            ]);
            $record->auditEvents()->create([
                'event_uuid' => $eventUuid,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $eventContext === [] ? null : $eventContext,
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $signatureHash === null ? null : $ipAddress,
                'signature_user_agent' => $signatureHash === null ? null : $userAgent,
                'occurred_at' => $occurredAt,
            ]);

            return $record->refresh();
        });
    }

    /** @return list<BatchReleaseStatus> */
    private function allowedFrom(BatchReleaseStatus $status): array
    {
        return match ($status) {
            BatchReleaseStatus::Draft => [BatchReleaseStatus::UnderReview, BatchReleaseStatus::Cancelled],
            BatchReleaseStatus::UnderReview => [
                BatchReleaseStatus::Released,
                BatchReleaseStatus::Rejected,
                BatchReleaseStatus::Cancelled,
            ],
            BatchReleaseStatus::Released, BatchReleaseStatus::Rejected, BatchReleaseStatus::Cancelled => [],
        };
    }

    private function requiresSignature(BatchReleaseStatus $status): bool
    {
        return in_array($status, [
            BatchReleaseStatus::Released,
            BatchReleaseStatus::Rejected,
            BatchReleaseStatus::Cancelled,
        ], true);
    }

    private function permissionFor(BatchReleaseStatus $status): string
    {
        return match ($status) {
            BatchReleaseStatus::UnderReview => 'Review:BatchRelease',
            BatchReleaseStatus::Released, BatchReleaseStatus::Rejected => 'Release:BatchRelease',
            BatchReleaseStatus::Cancelled => 'Manage:BatchRelease',
            default => 'Update:BatchRelease',
        };
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
