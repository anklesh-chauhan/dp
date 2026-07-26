<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ChangeControlTransitionService
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
        ChangeControl $changeControl,
        ChangeControlStatus $toStatus,
        User $actor,
        ?string $reason = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ChangeControl {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        $permission = $this->permissionFor($toStatus);

        if (! $actor->can($permission)) {
            throw new AuthorizationException(
                'You do not have permission to perform this change control transition.',
            );
        }

        return DB::transaction(function () use ($changeControl, $toStatus, $actor, $reason, $context, $ipAddress, $userAgent): ChangeControl {
            $record = ChangeControl::query()
                ->lockForUpdate()
                ->findOrFail($changeControl->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Change control cannot transition from {$fromStatus->value} to {$toStatus->value}.",
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
                ...$this->milestoneAttributes($toStatus, $occurredAt),
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

    /** @return list<ChangeControlStatus> */
    private function allowedFrom(ChangeControlStatus $status): array
    {
        return match ($status) {
            ChangeControlStatus::Draft => [
                ChangeControlStatus::Submitted,
                ChangeControlStatus::Cancelled,
            ],
            ChangeControlStatus::Submitted => [
                ChangeControlStatus::UnderReview,
                ChangeControlStatus::Rejected,
                ChangeControlStatus::Cancelled,
            ],
            ChangeControlStatus::UnderReview => [
                ChangeControlStatus::Approved,
                ChangeControlStatus::Rejected,
                ChangeControlStatus::Cancelled,
            ],
            ChangeControlStatus::Approved => [
                ChangeControlStatus::Implementing,
                ChangeControlStatus::Cancelled,
            ],
            ChangeControlStatus::Implementing => [
                ChangeControlStatus::EffectivenessReview,
            ],
            ChangeControlStatus::EffectivenessReview => [
                ChangeControlStatus::Closed,
            ],
            ChangeControlStatus::Closed,
            ChangeControlStatus::Rejected,
            ChangeControlStatus::Cancelled => [],
        };
    }

    private function permissionFor(ChangeControlStatus $status): string
    {
        return match ($status) {
            ChangeControlStatus::Submitted => 'Submit:ChangeControl',
            ChangeControlStatus::UnderReview,
            ChangeControlStatus::Rejected => 'Review:ChangeControl',
            ChangeControlStatus::Approved => 'Approve:ChangeControl',
            ChangeControlStatus::Implementing => 'Implement:ChangeControl',
            ChangeControlStatus::EffectivenessReview => 'VerifyEffectiveness:ChangeControl',
            ChangeControlStatus::Closed => 'Close:ChangeControl',
            ChangeControlStatus::Cancelled => 'Manage:ChangeControl',
            ChangeControlStatus::Draft => 'Update:ChangeControl',
        };
    }

    private function requiresSignature(ChangeControlStatus $status): bool
    {
        return in_array($status, [
            ChangeControlStatus::Approved,
            ChangeControlStatus::Rejected,
            ChangeControlStatus::Cancelled,
            ChangeControlStatus::EffectivenessReview,
            ChangeControlStatus::Closed,
        ], true);
    }

    /** @return array<string, Carbon> */
    private function milestoneAttributes(
        ChangeControlStatus $status,
        Carbon $occurredAt,
    ): array {
        return match ($status) {
            ChangeControlStatus::Submitted => ['submitted_at' => $occurredAt],
            ChangeControlStatus::Approved => ['approved_at' => $occurredAt],
            ChangeControlStatus::EffectivenessReview => ['implemented_at' => $occurredAt],
            ChangeControlStatus::Closed => [
                'effectiveness_verified_at' => $occurredAt,
                'closed_at' => $occurredAt,
            ],
            default => [],
        };
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
