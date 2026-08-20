<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\EquipmentQualification;
use App\Domain\Shared\Services\ContentBoundElectronicSignatureIssuer;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class EquipmentQualificationTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ContentBoundElectronicSignatureIssuer $contentBoundSignatures,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function transition(
        EquipmentQualification $qualification,
        EquipmentQualificationStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): EquipmentQualification {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this equipment qualification transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every equipment qualification transition.',
            ]);
        }

        return DB::transaction(function () use ($qualification, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): EquipmentQualification {
            $record = EquipmentQualification::query()->lockForUpdate()->findOrFail($qualification->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Equipment qualification cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $attributes = $this->attributesFor($record, $toStatus, $actor, $context);
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

            $record->update([
                'status' => $toStatus,
                ...$attributes,
            ]);
            $record->auditEvents()->create([
                'event_uuid' => $eventUuid,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $eventContext,
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $signatureHash === null ? null : $ipAddress,
                'signature_user_agent' => $signatureHash === null ? null : $userAgent,
                'occurred_at' => $occurredAt,
            ]);

            return $record->refresh();
        });
    }

    /** @return list<EquipmentQualificationStatus> */
    private function allowedFrom(EquipmentQualificationStatus $status): array
    {
        return match ($status) {
            EquipmentQualificationStatus::Draft => [
                EquipmentQualificationStatus::InProgress,
                EquipmentQualificationStatus::Cancelled,
            ],
            EquipmentQualificationStatus::InProgress => [
                EquipmentQualificationStatus::UnderReview,
                EquipmentQualificationStatus::Failed,
                EquipmentQualificationStatus::Cancelled,
            ],
            EquipmentQualificationStatus::UnderReview => [
                EquipmentQualificationStatus::Approved,
                EquipmentQualificationStatus::Failed,
                EquipmentQualificationStatus::InProgress,
                EquipmentQualificationStatus::Cancelled,
            ],
            EquipmentQualificationStatus::Approved,
            EquipmentQualificationStatus::Failed,
            EquipmentQualificationStatus::Cancelled => [],
        };
    }

    private function permissionFor(EquipmentQualificationStatus $status): string
    {
        return match ($status) {
            EquipmentQualificationStatus::InProgress => 'Execute:EquipmentQualification',
            EquipmentQualificationStatus::UnderReview => 'Execute:EquipmentQualification',
            EquipmentQualificationStatus::Approved => 'Approve:EquipmentQualification',
            EquipmentQualificationStatus::Failed => 'Review:EquipmentQualification',
            EquipmentQualificationStatus::Cancelled => 'Manage:EquipmentQualification',
            EquipmentQualificationStatus::Draft => 'Update:EquipmentQualification',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function attributesFor(
        EquipmentQualification $record,
        EquipmentQualificationStatus $toStatus,
        User $actor,
        array $context,
    ): array {
        $occurredAt = now();

        return match ($toStatus) {
            EquipmentQualificationStatus::InProgress => [
                'executed_by' => $record->executed_by ?? $actor->getKey(),
                'started_at' => $record->started_at ?? $occurredAt,
            ],
            EquipmentQualificationStatus::UnderReview => [
                'completed_at' => $record->completed_at ?? $occurredAt,
            ],
            EquipmentQualificationStatus::Approved => [
                'reviewed_by' => $actor->getKey(),
                'approved_at' => $occurredAt,
                'completed_at' => $record->completed_at ?? $occurredAt,
            ],
            EquipmentQualificationStatus::Failed => [
                'reviewed_by' => $actor->getKey(),
                'completed_at' => $record->completed_at ?? $occurredAt,
                'deviation_id' => $this->resolveDeviationId($context, $record),
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws ValidationException
     */
    private function resolveDeviationId(array $context, EquipmentQualification $record): ?int
    {
        if (! array_key_exists('deviation_id', $context)) {
            return $record->deviation_id;
        }

        $raw = $context['deviation_id'];

        if ($raw === null || $raw === '') {
            return null;
        }

        $deviationId = (int) $raw;

        if (! Deviation::query()->whereKey($deviationId)->exists()) {
            throw ValidationException::withMessages([
                'deviation_id' => 'The linked deviation does not exist.',
            ]);
        }

        return $deviationId;
    }

    private function requiresSignature(EquipmentQualificationStatus $status): bool
    {
        return in_array($status, [
            EquipmentQualificationStatus::Approved,
            EquipmentQualificationStatus::Failed,
            EquipmentQualificationStatus::Cancelled,
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
