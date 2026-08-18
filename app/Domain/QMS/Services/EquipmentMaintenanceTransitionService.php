<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Models\EquipmentMaintenance;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class EquipmentMaintenanceTransitionService
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
        EquipmentMaintenance $maintenance,
        EquipmentMaintenanceStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): EquipmentMaintenance {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this equipment maintenance transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every equipment maintenance transition.',
            ]);
        }

        return DB::transaction(function () use ($maintenance, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): EquipmentMaintenance {
            $record = EquipmentMaintenance::query()->lockForUpdate()->findOrFail($maintenance->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Equipment maintenance cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $attributes = $this->attributesFor($record, $toStatus, $actor, $context);
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
                ...$attributes,
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

    /** @return list<EquipmentMaintenanceStatus> */
    private function allowedFrom(EquipmentMaintenanceStatus $status): array
    {
        return match ($status) {
            EquipmentMaintenanceStatus::Planned => [
                EquipmentMaintenanceStatus::InProgress,
                EquipmentMaintenanceStatus::Cancelled,
            ],
            EquipmentMaintenanceStatus::InProgress => [
                EquipmentMaintenanceStatus::Completed,
                EquipmentMaintenanceStatus::Cancelled,
            ],
            EquipmentMaintenanceStatus::Completed,
            EquipmentMaintenanceStatus::Cancelled => [],
        };
    }

    private function permissionFor(EquipmentMaintenanceStatus $status): string
    {
        return match ($status) {
            EquipmentMaintenanceStatus::InProgress => 'Perform:EquipmentMaintenance',
            EquipmentMaintenanceStatus::Completed => 'Perform:EquipmentMaintenance',
            EquipmentMaintenanceStatus::Cancelled => 'Manage:EquipmentMaintenance',
            EquipmentMaintenanceStatus::Planned => 'Update:EquipmentMaintenance',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function attributesFor(
        EquipmentMaintenance $record,
        EquipmentMaintenanceStatus $toStatus,
        User $actor,
        array $context,
    ): array {
        $occurredAt = now();

        return match ($toStatus) {
            EquipmentMaintenanceStatus::InProgress => [
                'performed_by' => $record->performed_by ?? $actor->getKey(),
            ],
            EquipmentMaintenanceStatus::Completed => [
                'performed_by' => $record->performed_by ?? $actor->getKey(),
                'completed_at' => $record->completed_at ?? $occurredAt,
                'notes' => $context['notes'] ?? $record->notes,
            ],
            default => [],
        };
    }

    private function requiresSignature(EquipmentMaintenanceStatus $status): bool
    {
        return in_array($status, [
            EquipmentMaintenanceStatus::Completed,
            EquipmentMaintenanceStatus::Cancelled,
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
