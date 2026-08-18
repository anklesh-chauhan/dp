<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ScheduleMGapAssessmentTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(
        ScheduleMGapAssessment $assessment,
        ScheduleMGapAssessmentStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ScheduleMGapAssessment {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this Schedule M gap assessment transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every Schedule M gap assessment transition.',
            ]);
        }

        return DB::transaction(function () use (
            $assessment,
            $toStatus,
            $actor,
            $normalizedReason,
            $context,
            $ipAddress,
            $userAgent,
        ): ScheduleMGapAssessment {
            $record = ScheduleMGapAssessment::query()->lockForUpdate()->findOrFail($assessment->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Schedule M gap assessment cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $this->assertReadyFor($record, $toStatus, $actor);

            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $signatureHash = $this->requiresSignature($toStatus)
                ? $this->electronicSignatureHasher->issueFor(
                    signer: $actor,
                    recordKey: $eventUuid,
                    meaning: $toStatus->value,
                    signedAt: $occurredAt,
                    reason: $normalizedReason,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                )
                : null;

            $record->update([
                'status' => $toStatus,
                ...($toStatus === ScheduleMGapAssessmentStatus::Approved ? [
                    'approved_at' => $occurredAt,
                ] : []),
                ...($toStatus === ScheduleMGapAssessmentStatus::Closed ? [
                    'closed_at' => $occurredAt,
                ] : []),
            ]);

            $record->auditEvents()->create([
                'event_uuid' => $eventUuid,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'event_type' => 'transition',
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

    /** @return list<ScheduleMGapAssessmentStatus> */
    private function allowedFrom(ScheduleMGapAssessmentStatus $status): array
    {
        return match ($status) {
            ScheduleMGapAssessmentStatus::Draft => [
                ScheduleMGapAssessmentStatus::InProgress,
                ScheduleMGapAssessmentStatus::Cancelled,
            ],
            ScheduleMGapAssessmentStatus::InProgress => [
                ScheduleMGapAssessmentStatus::UnderReview,
                ScheduleMGapAssessmentStatus::Cancelled,
            ],
            ScheduleMGapAssessmentStatus::UnderReview => [
                ScheduleMGapAssessmentStatus::Approved,
                ScheduleMGapAssessmentStatus::InProgress,
                ScheduleMGapAssessmentStatus::Cancelled,
            ],
            ScheduleMGapAssessmentStatus::Approved => [
                ScheduleMGapAssessmentStatus::Closed,
            ],
            ScheduleMGapAssessmentStatus::Closed,
            ScheduleMGapAssessmentStatus::Cancelled => [],
        };
    }

    private function permissionFor(ScheduleMGapAssessmentStatus $status): string
    {
        return match ($status) {
            ScheduleMGapAssessmentStatus::InProgress => 'Conduct:ScheduleMGapAssessment',
            ScheduleMGapAssessmentStatus::UnderReview => 'Conduct:ScheduleMGapAssessment',
            ScheduleMGapAssessmentStatus::Approved => 'Approve:ScheduleMGapAssessment',
            ScheduleMGapAssessmentStatus::Closed => 'Close:ScheduleMGapAssessment',
            ScheduleMGapAssessmentStatus::Cancelled => 'Manage:ScheduleMGapAssessment',
            ScheduleMGapAssessmentStatus::Draft => 'Update:ScheduleMGapAssessment',
        };
    }

    private function assertReadyFor(
        ScheduleMGapAssessment $assessment,
        ScheduleMGapAssessmentStatus $toStatus,
        User $actor,
    ): void {
        if ($toStatus === ScheduleMGapAssessmentStatus::InProgress
            && $assessment->status === ScheduleMGapAssessmentStatus::Draft) {
            if (blank($assessment->title) || blank($assessment->site_name) || $assessment->owner_id === null) {
                throw ValidationException::withMessages([
                    'owner_id' => 'Title, site name, and owner are required to begin the gap assessment.',
                ]);
            }
        }

        if ($toStatus === ScheduleMGapAssessmentStatus::UnderReview) {
            $openItems = $assessment->items()
                ->where('status', ScheduleMGapItemStatus::NotAssessed->value)
                ->count();

            if ($openItems > 0) {
                throw ValidationException::withMessages([
                    'items' => 'All clause items must be assessed before submitting for review.',
                ]);
            }
        }

        if ($toStatus === ScheduleMGapAssessmentStatus::Approved) {
            if (in_array((int) $actor->getKey(), array_filter([
                $assessment->created_by,
                $assessment->owner_id,
            ]), true)) {
                throw ValidationException::withMessages([
                    'approved_by' => 'The creator or owner cannot independently approve this gap assessment.',
                ]);
            }
        }
    }

    private function requiresSignature(ScheduleMGapAssessmentStatus $status): bool
    {
        return in_array($status, [
            ScheduleMGapAssessmentStatus::Approved,
            ScheduleMGapAssessmentStatus::Closed,
            ScheduleMGapAssessmentStatus::Cancelled,
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
