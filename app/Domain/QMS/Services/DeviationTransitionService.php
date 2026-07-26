<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DeviationTransitionService
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
        Deviation $deviation,
        DeviationStatus $toStatus,
        User $actor,
        ?string $reason = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Deviation {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException(
                'You do not have permission to perform this deviation transition.',
            );
        }

        return DB::transaction(function () use ($deviation, $toStatus, $actor, $reason, $context, $ipAddress, $userAgent): Deviation {
            $record = Deviation::query()->lockForUpdate()->findOrFail($deviation->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Deviation cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            if (
                $toStatus === DeviationStatus::InvestigationComplete
                && (
                    ! $record->investigations()->exists()
                    || $record->investigations()
                        ->where('status', '!=', InvestigationStatus::Completed->value)
                        ->exists()
                )
            ) {
                throw ValidationException::withMessages([
                    'investigations' => 'All linked investigations must be completed before the deviation can leave investigation.',
                ]);
            }

            if (
                $fromStatus === DeviationStatus::CapaRequired
                && $toStatus === DeviationStatus::EffectivenessReview
                && (
                    ! $record->capas()->exists()
                    || $record->capas()
                        ->whereNotIn('status', [
                            CapaStatus::Effective->value,
                            CapaStatus::Closed->value,
                        ])
                        ->exists()
                )
            ) {
                throw ValidationException::withMessages([
                    'capas' => 'All linked CAPAs must have a successful effectiveness decision before deviation effectiveness review.',
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
                ...($toStatus === DeviationStatus::Closed ? ['closed_at' => $occurredAt] : []),
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

    /** @return list<DeviationStatus> */
    private function allowedFrom(DeviationStatus $status): array
    {
        return match ($status) {
            DeviationStatus::Draft => [
                DeviationStatus::Open,
                DeviationStatus::Cancelled,
            ],
            DeviationStatus::Open => [
                DeviationStatus::Draft,
                DeviationStatus::UnderInvestigation,
                DeviationStatus::Rejected,
                DeviationStatus::Cancelled,
            ],
            DeviationStatus::UnderInvestigation => [
                DeviationStatus::InvestigationComplete,
                DeviationStatus::Cancelled,
            ],
            DeviationStatus::InvestigationComplete => [
                DeviationStatus::CapaRequired,
                DeviationStatus::EffectivenessReview,
            ],
            DeviationStatus::CapaRequired => [
                DeviationStatus::EffectivenessReview,
            ],
            DeviationStatus::EffectivenessReview => [
                DeviationStatus::Closed,
            ],
            DeviationStatus::Closed,
            DeviationStatus::Rejected,
            DeviationStatus::Cancelled => [],
        };
    }

    private function permissionFor(DeviationStatus $status): string
    {
        return match ($status) {
            DeviationStatus::Open => 'Submit:Deviation',
            DeviationStatus::UnderInvestigation,
            DeviationStatus::InvestigationComplete,
            DeviationStatus::CapaRequired,
            DeviationStatus::Rejected => 'Investigate:Deviation',
            DeviationStatus::EffectivenessReview => 'VerifyEffectiveness:Deviation',
            DeviationStatus::Closed => 'Close:Deviation',
            DeviationStatus::Cancelled => 'Manage:Deviation',
            DeviationStatus::Draft => 'Investigate:Deviation',
        };
    }

    private function requiresSignature(DeviationStatus $status): bool
    {
        return in_array($status, [
            DeviationStatus::Rejected,
            DeviationStatus::Cancelled,
            DeviationStatus::Draft,
            DeviationStatus::InvestigationComplete,
            DeviationStatus::EffectivenessReview,
            DeviationStatus::Closed,
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
