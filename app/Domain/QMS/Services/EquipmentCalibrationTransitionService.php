<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\EquipmentCalibrationResult;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Domain\Shared\Services\ContentBoundElectronicSignatureIssuer;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class EquipmentCalibrationTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ContentBoundElectronicSignatureIssuer $contentBoundSignatures,
        private readonly CalibrationGate $calibrationGate,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function transition(
        EquipmentCalibration $calibration,
        EquipmentCalibrationStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): EquipmentCalibration {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this equipment calibration transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every equipment calibration transition.',
            ]);
        }

        return DB::transaction(function () use ($calibration, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): EquipmentCalibration {
            $record = EquipmentCalibration::query()->lockForUpdate()->findOrFail($calibration->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Equipment calibration cannot transition from {$fromStatus->value} to {$toStatus->value}.",
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

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function verify(
        EquipmentCalibration $calibration,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): EquipmentCalibration {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Verify:EquipmentCalibration')) {
            throw new AuthorizationException('You do not have permission to verify this equipment calibration.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required to verify an equipment calibration.',
            ]);
        }

        return DB::transaction(function () use ($calibration, $actor, $normalizedReason, $context, $ipAddress, $userAgent): EquipmentCalibration {
            $record = EquipmentCalibration::query()->lockForUpdate()->findOrFail($calibration->getKey());

            if (! in_array($record->status, [
                EquipmentCalibrationStatus::Completed,
                EquipmentCalibrationStatus::OutOfTolerance,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only completed or out-of-tolerance calibrations can be verified.',
                ]);
            }

            if ($record->verified_by !== null) {
                throw ValidationException::withMessages([
                    'verified_by' => 'This calibration has already been verified.',
                ]);
            }

            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $eventContext = [
                ...$this->sanitize($context),
                'action' => 'verified',
            ];
            [$signatureHash, $eventContext] = $this->contentBoundSignatures->issue(
                signer: $actor,
                subject: $record,
                recordKey: $eventUuid,
                meaning: 'verified',
                signedAt: $occurredAt,
                reason: $normalizedReason,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                context: $eventContext,
            );

            $record->update([
                'verified_by' => $actor->getKey(),
            ]);
            $record->auditEvents()->create([
                'event_uuid' => $eventUuid,
                'from_status' => $record->status,
                'to_status' => $record->status,
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $eventContext,
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $ipAddress,
                'signature_user_agent' => $userAgent,
                'occurred_at' => $occurredAt,
            ]);

            return $record->refresh();
        });
    }

    /** @return list<EquipmentCalibrationStatus> */
    private function allowedFrom(EquipmentCalibrationStatus $status): array
    {
        return match ($status) {
            EquipmentCalibrationStatus::Scheduled => [
                EquipmentCalibrationStatus::InProgress,
                EquipmentCalibrationStatus::Cancelled,
            ],
            EquipmentCalibrationStatus::InProgress => [
                EquipmentCalibrationStatus::Completed,
                EquipmentCalibrationStatus::OutOfTolerance,
                EquipmentCalibrationStatus::Cancelled,
            ],
            EquipmentCalibrationStatus::Completed,
            EquipmentCalibrationStatus::OutOfTolerance,
            EquipmentCalibrationStatus::Cancelled => [],
        };
    }

    private function permissionFor(EquipmentCalibrationStatus $status): string
    {
        return match ($status) {
            EquipmentCalibrationStatus::InProgress => 'Perform:EquipmentCalibration',
            EquipmentCalibrationStatus::Completed => 'Perform:EquipmentCalibration',
            EquipmentCalibrationStatus::OutOfTolerance => 'Perform:EquipmentCalibration',
            EquipmentCalibrationStatus::Cancelled => 'Manage:EquipmentCalibration',
            EquipmentCalibrationStatus::Scheduled => 'Update:EquipmentCalibration',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function attributesFor(
        EquipmentCalibration $record,
        EquipmentCalibrationStatus $toStatus,
        User $actor,
        array $context,
    ): array {
        $occurredAt = now();

        return match ($toStatus) {
            EquipmentCalibrationStatus::InProgress => [
                'performed_by' => $record->performed_by ?? $actor->getKey(),
            ],
            EquipmentCalibrationStatus::Completed => [
                'performed_by' => $record->performed_by ?? $actor->getKey(),
                'performed_at' => $record->performed_at ?? $occurredAt,
                'result' => $this->resolveResult($context, EquipmentCalibrationResult::Pass),
                'certificate_reference' => $context['certificate_reference'] ?? $record->certificate_reference,
                'next_due_at' => $context['next_due_at'] ?? $record->next_due_at,
                'notes' => $context['notes'] ?? $record->notes,
            ],
            EquipmentCalibrationStatus::OutOfTolerance => $this->outOfToleranceAttributes($record, $actor, $context, $occurredAt),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function outOfToleranceAttributes(
        EquipmentCalibration $record,
        User $actor,
        array $context,
        mixed $occurredAt,
    ): array {
        $deviationId = $this->resolveRequiredDeviationId($context, $record);
        $this->calibrationGate->assertOutOfToleranceHasDeviation($deviationId);

        return [
            'performed_by' => $record->performed_by ?? $actor->getKey(),
            'performed_at' => $record->performed_at ?? $occurredAt,
            'result' => EquipmentCalibrationResult::OutOfTolerance,
            'deviation_id' => $deviationId,
            'certificate_reference' => $context['certificate_reference'] ?? $record->certificate_reference,
            'next_due_at' => $context['next_due_at'] ?? $record->next_due_at,
            'notes' => $context['notes'] ?? $record->notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws ValidationException
     */
    private function resolveResult(array $context, EquipmentCalibrationResult $default): EquipmentCalibrationResult
    {
        if (! array_key_exists('result', $context) || $context['result'] === null || $context['result'] === '') {
            return $default;
        }

        $raw = $context['result'];
        $result = $raw instanceof EquipmentCalibrationResult
            ? $raw
            : EquipmentCalibrationResult::tryFrom((string) $raw);

        if ($result === null) {
            throw ValidationException::withMessages([
                'result' => 'The calibration result is invalid.',
            ]);
        }

        if ($result === EquipmentCalibrationResult::OutOfTolerance) {
            throw ValidationException::withMessages([
                'result' => 'Out-of-tolerance results must use the out-of-tolerance transition with a linked deviation.',
            ]);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws ValidationException
     */
    private function resolveRequiredDeviationId(array $context, EquipmentCalibration $record): int
    {
        $raw = $context['deviation_id'] ?? $record->deviation_id;

        if ($raw === null || $raw === '') {
            throw ValidationException::withMessages([
                'deviation_id' => 'Out-of-tolerance calibrations must be linked to a deviation before close.',
            ]);
        }

        $deviationId = (int) $raw;

        if (! Deviation::query()->whereKey($deviationId)->exists()) {
            throw ValidationException::withMessages([
                'deviation_id' => 'The linked deviation does not exist.',
            ]);
        }

        return $deviationId;
    }

    private function requiresSignature(EquipmentCalibrationStatus $status): bool
    {
        return in_array($status, [
            EquipmentCalibrationStatus::Completed,
            EquipmentCalibrationStatus::OutOfTolerance,
            EquipmentCalibrationStatus::Cancelled,
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
