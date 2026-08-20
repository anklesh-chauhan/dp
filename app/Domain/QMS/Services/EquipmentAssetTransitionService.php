<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Domain\Shared\Services\ContentBoundElectronicSignatureIssuer;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class EquipmentAssetTransitionService
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
        EquipmentAsset $asset,
        EquipmentAssetStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): EquipmentAsset {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this equipment asset transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every equipment asset transition.',
            ]);
        }

        return DB::transaction(function () use ($asset, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): EquipmentAsset {
            $record = EquipmentAsset::query()->lockForUpdate()->findOrFail($asset->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Equipment asset cannot transition from {$fromStatus->value} to {$toStatus->value}.",
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

            $record->update(['status' => $toStatus]);
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

    /** @return list<EquipmentAssetStatus> */
    private function allowedFrom(EquipmentAssetStatus $status): array
    {
        return match ($status) {
            EquipmentAssetStatus::Active => [
                EquipmentAssetStatus::Inactive,
                EquipmentAssetStatus::Decommissioned,
            ],
            EquipmentAssetStatus::Inactive => [
                EquipmentAssetStatus::Active,
                EquipmentAssetStatus::Decommissioned,
            ],
            EquipmentAssetStatus::Decommissioned => [],
        };
    }

    private function permissionFor(EquipmentAssetStatus $status): string
    {
        return match ($status) {
            EquipmentAssetStatus::Active,
            EquipmentAssetStatus::Inactive => 'Update:EquipmentAsset',
            EquipmentAssetStatus::Decommissioned => 'Manage:EquipmentAsset',
        };
    }

    private function requiresSignature(EquipmentAssetStatus $status): bool
    {
        return $status === EquipmentAssetStatus::Decommissioned;
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
