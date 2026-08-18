<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ValidationMasterPlanTransitionService
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
        ValidationMasterPlan $plan,
        ValidationMasterPlanStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ValidationMasterPlan {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this validation master plan transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every validation master plan transition.',
            ]);
        }

        return DB::transaction(function () use ($plan, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): ValidationMasterPlan {
            $record = ValidationMasterPlan::query()->lockForUpdate()->findOrFail($plan->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Validation master plan cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

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
                ...$this->attributesFor($toStatus, $actor, $occurredAt),
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

    /** @return list<ValidationMasterPlanStatus> */
    private function allowedFrom(ValidationMasterPlanStatus $status): array
    {
        return match ($status) {
            ValidationMasterPlanStatus::Draft => [
                ValidationMasterPlanStatus::Active,
                ValidationMasterPlanStatus::Cancelled,
            ],
            ValidationMasterPlanStatus::Active => [
                ValidationMasterPlanStatus::UnderRevision,
                ValidationMasterPlanStatus::Retired,
            ],
            ValidationMasterPlanStatus::UnderRevision => [
                ValidationMasterPlanStatus::Active,
                ValidationMasterPlanStatus::Retired,
                ValidationMasterPlanStatus::Cancelled,
            ],
            ValidationMasterPlanStatus::Retired,
            ValidationMasterPlanStatus::Cancelled => [],
        };
    }

    private function permissionFor(ValidationMasterPlanStatus $status): string
    {
        return match ($status) {
            ValidationMasterPlanStatus::Active => 'Approve:ValidationMasterPlan',
            ValidationMasterPlanStatus::UnderRevision => 'Update:ValidationMasterPlan',
            ValidationMasterPlanStatus::Retired => 'Retire:ValidationMasterPlan',
            ValidationMasterPlanStatus::Cancelled => 'Manage:ValidationMasterPlan',
            ValidationMasterPlanStatus::Draft => 'Update:ValidationMasterPlan',
        };
    }

    /** @return array<string, mixed> */
    private function attributesFor(ValidationMasterPlanStatus $toStatus, User $actor, mixed $occurredAt): array
    {
        return match ($toStatus) {
            ValidationMasterPlanStatus::Active => [
                'approved_by' => $actor->getKey(),
                'approved_at' => $occurredAt,
            ],
            ValidationMasterPlanStatus::Retired => [
                'retired_at' => $occurredAt,
            ],
            default => [],
        };
    }

    private function requiresSignature(ValidationMasterPlanStatus $status): bool
    {
        return in_array($status, [
            ValidationMasterPlanStatus::Active,
            ValidationMasterPlanStatus::Retired,
            ValidationMasterPlanStatus::Cancelled,
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
