<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Investigation;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class InvestigationTransitionService
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
        Investigation $investigation,
        InvestigationStatus $toStatus,
        User $actor,
        ?string $reason = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Investigation {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException(
                'You do not have permission to perform this investigation transition.',
            );
        }

        return DB::transaction(function () use ($investigation, $toStatus, $actor, $reason, $context, $ipAddress, $userAgent): Investigation {
            $record = Investigation::query()->lockForUpdate()->findOrFail($investigation->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Investigation cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            if (
                $toStatus === InvestigationStatus::Completed
                && (blank($record->root_cause) || blank($record->conclusion))
            ) {
                throw ValidationException::withMessages([
                    'investigation' => 'Root cause and conclusion are required before completing an investigation.',
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

    /** @return list<InvestigationStatus> */
    private function allowedFrom(InvestigationStatus $status): array
    {
        return match ($status) {
            InvestigationStatus::Draft => [
                InvestigationStatus::InProgress,
                InvestigationStatus::Cancelled,
            ],
            InvestigationStatus::InProgress => [
                InvestigationStatus::PendingReview,
                InvestigationStatus::Cancelled,
            ],
            InvestigationStatus::PendingReview => [
                InvestigationStatus::InProgress,
                InvestigationStatus::Completed,
                InvestigationStatus::Cancelled,
            ],
            InvestigationStatus::Completed,
            InvestigationStatus::Cancelled => [],
        };
    }

    private function permissionFor(InvestigationStatus $status): string
    {
        return match ($status) {
            InvestigationStatus::InProgress => 'Update:Investigation',
            InvestigationStatus::PendingReview => 'Review:Investigation',
            InvestigationStatus::Completed => 'Complete:Investigation',
            InvestigationStatus::Cancelled => 'Manage:Investigation',
            InvestigationStatus::Draft => 'Update:Investigation',
        };
    }

    private function requiresSignature(InvestigationStatus $status): bool
    {
        return in_array($status, [
            InvestigationStatus::Completed,
            InvestigationStatus::Cancelled,
        ], true);
    }

    /** @return array<string, Carbon> */
    private function milestones(
        InvestigationStatus $status,
        Carbon $occurredAt,
    ): array {
        return match ($status) {
            InvestigationStatus::InProgress => ['started_at' => $occurredAt],
            InvestigationStatus::Completed => ['completed_at' => $occurredAt],
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
