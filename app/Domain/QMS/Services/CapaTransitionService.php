<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Models\Capa;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CapaTransitionService
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
        Capa $capa,
        CapaStatus $toStatus,
        User $actor,
        ?string $reason = null,
        ?string $effectivenessResult = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Capa {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this CAPA transition.');
        }

        return DB::transaction(function () use ($capa, $toStatus, $actor, $reason, $effectivenessResult, $context, $ipAddress, $userAgent): Capa {
            $record = Capa::query()->lockForUpdate()->findOrFail($capa->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "CAPA cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            if ($toStatus === CapaStatus::PendingEffectiveness && blank($record->action_plan)) {
                throw ValidationException::withMessages([
                    'action_plan' => 'An action plan is required before CAPA implementation can be completed.',
                ]);
            }

            $normalizedEffectivenessResult = filled($effectivenessResult)
                ? trim((string) $effectivenessResult)
                : null;

            if (
                in_array($toStatus, [CapaStatus::Effective, CapaStatus::Ineffective], true)
                && $normalizedEffectivenessResult === null
            ) {
                throw ValidationException::withMessages([
                    'effectiveness_result' => 'An effectiveness result is required for the CAPA effectiveness decision.',
                ]);
            }

            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $normalizedReason = filled($reason) ? trim((string) $reason) : null;
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
                ...$this->milestones($toStatus, $occurredAt),
                ...($normalizedEffectivenessResult === null
                    ? []
                    : ['effectiveness_result' => $normalizedEffectivenessResult]),
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

    /** @return list<CapaStatus> */
    private function allowedFrom(CapaStatus $status): array
    {
        return match ($status) {
            CapaStatus::Draft => [CapaStatus::Planned, CapaStatus::Cancelled],
            CapaStatus::Planned => [CapaStatus::InProgress, CapaStatus::Cancelled],
            CapaStatus::InProgress => [CapaStatus::PendingEffectiveness, CapaStatus::Cancelled],
            CapaStatus::PendingEffectiveness => [
                CapaStatus::Effective,
                CapaStatus::Ineffective,
                CapaStatus::Cancelled,
            ],
            CapaStatus::Ineffective => [CapaStatus::InProgress, CapaStatus::Cancelled],
            CapaStatus::Effective => [CapaStatus::Closed],
            CapaStatus::Closed,
            CapaStatus::Cancelled => [],
        };
    }

    private function permissionFor(CapaStatus $status): string
    {
        return match ($status) {
            CapaStatus::Draft,
            CapaStatus::Planned => 'Update:Capa',
            CapaStatus::InProgress,
            CapaStatus::PendingEffectiveness => 'Implement:Capa',
            CapaStatus::Effective,
            CapaStatus::Ineffective => 'VerifyEffectiveness:Capa',
            CapaStatus::Closed => 'Close:Capa',
            CapaStatus::Cancelled => 'Manage:Capa',
        };
    }

    private function requiresSignature(CapaStatus $status): bool
    {
        return in_array($status, [
            CapaStatus::PendingEffectiveness,
            CapaStatus::Effective,
            CapaStatus::Ineffective,
            CapaStatus::Closed,
            CapaStatus::Cancelled,
        ], true);
    }

    /** @return array<string, Carbon> */
    private function milestones(CapaStatus $status, Carbon $occurredAt): array
    {
        return match ($status) {
            CapaStatus::PendingEffectiveness => ['completed_at' => $occurredAt],
            CapaStatus::Effective,
            CapaStatus::Ineffective => ['effectiveness_verified_at' => $occurredAt],
            CapaStatus::Closed => ['closed_at' => $occurredAt],
            default => [],
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
