<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LaboratoryOosTransitionService
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
        LaboratoryOosEvent $event,
        LaboratoryOosStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?LaboratoryOosPhaseOutcome $phaseOneOutcome = null,
        ?string $phaseOneNotes = null,
        ?LaboratoryOosPhaseOutcome $phaseTwoOutcome = null,
        ?string $phaseTwoNotes = null,
        ?string $invalidationJustification = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): LaboratoryOosEvent {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this laboratory OOS transition.');
        }

        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every laboratory OOS transition.',
            ]);
        }

        return DB::transaction(function () use (
            $event,
            $toStatus,
            $actor,
            $normalizedReason,
            $context,
            $phaseOneOutcome,
            $phaseOneNotes,
            $phaseTwoOutcome,
            $phaseTwoNotes,
            $invalidationJustification,
            $ipAddress,
            $userAgent,
        ): LaboratoryOosEvent {
            $record = LaboratoryOosEvent::query()->lockForUpdate()->findOrFail($event->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Laboratory OOS event cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $updates = [];

            if ($phaseOneOutcome !== null) {
                $updates['phase_one_outcome'] = $phaseOneOutcome;
            }

            if ($phaseOneNotes !== null) {
                $updates['phase_one_notes'] = trim($phaseOneNotes) !== '' ? trim($phaseOneNotes) : null;
            }

            if ($phaseTwoOutcome !== null) {
                $updates['phase_two_outcome'] = $phaseTwoOutcome;
            }

            if ($phaseTwoNotes !== null) {
                $updates['phase_two_notes'] = trim($phaseTwoNotes) !== '' ? trim($phaseTwoNotes) : null;
            }

            if ($invalidationJustification !== null) {
                $updates['invalidation_justification'] = trim($invalidationJustification) !== ''
                    ? trim($invalidationJustification)
                    : null;
            }

            if ($updates !== []) {
                $record->fill($updates);
            }

            $this->validateGates($record, $fromStatus, $toStatus);

            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
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

            $record->fill([
                'status' => $toStatus,
                ...($toStatus === LaboratoryOosStatus::PhaseOne && $record->started_at === null
                    ? ['started_at' => $occurredAt]
                    : []),
                ...($fromStatus === LaboratoryOosStatus::PhaseOne && $toStatus !== LaboratoryOosStatus::Cancelled
                    ? ['phase_one_completed_at' => $occurredAt]
                    : []),
                ...($fromStatus === LaboratoryOosStatus::PhaseTwo && $toStatus !== LaboratoryOosStatus::Cancelled
                    ? ['phase_two_completed_at' => $occurredAt]
                    : []),
                ...($toStatus === LaboratoryOosStatus::Closed ? ['closed_at' => $occurredAt] : []),
            ]);
            $record->save();

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

    /** @return list<LaboratoryOosStatus> */
    private function allowedFrom(LaboratoryOosStatus $status): array
    {
        return match ($status) {
            LaboratoryOosStatus::Draft => [
                LaboratoryOosStatus::PhaseOne,
                LaboratoryOosStatus::Cancelled,
            ],
            LaboratoryOosStatus::PhaseOne => [
                LaboratoryOosStatus::PhaseTwo,
                LaboratoryOosStatus::InvalidationProposed,
                LaboratoryOosStatus::Confirmed,
                LaboratoryOosStatus::Cancelled,
            ],
            LaboratoryOosStatus::PhaseTwo => [
                LaboratoryOosStatus::Confirmed,
                LaboratoryOosStatus::InvalidationProposed,
                LaboratoryOosStatus::Cancelled,
            ],
            LaboratoryOosStatus::InvalidationProposed => [
                LaboratoryOosStatus::Closed,
                LaboratoryOosStatus::PhaseTwo,
                LaboratoryOosStatus::Cancelled,
            ],
            LaboratoryOosStatus::Confirmed => [
                LaboratoryOosStatus::Closed,
                LaboratoryOosStatus::Cancelled,
            ],
            LaboratoryOosStatus::Closed,
            LaboratoryOosStatus::Cancelled => [],
        };
    }

    private function permissionFor(LaboratoryOosStatus $status): string
    {
        return match ($status) {
            LaboratoryOosStatus::PhaseOne,
            LaboratoryOosStatus::PhaseTwo,
            LaboratoryOosStatus::InvalidationProposed => 'Investigate:LaboratoryOosEvent',
            LaboratoryOosStatus::Confirmed => 'Confirm:LaboratoryOosEvent',
            LaboratoryOosStatus::Closed => 'Close:LaboratoryOosEvent',
            LaboratoryOosStatus::Cancelled => 'Manage:LaboratoryOosEvent',
            LaboratoryOosStatus::Draft => 'Update:LaboratoryOosEvent',
        };
    }

    /**
     * @throws ValidationException
     */
    private function validateGates(
        LaboratoryOosEvent $event,
        LaboratoryOosStatus $fromStatus,
        LaboratoryOosStatus $toStatus,
    ): void {
        if (
            $fromStatus === LaboratoryOosStatus::PhaseOne
            && $toStatus !== LaboratoryOosStatus::Cancelled
            && ($event->phase_one_outcome === null || $event->phase_one_outcome === LaboratoryOosPhaseOutcome::Pending)
        ) {
            throw ValidationException::withMessages([
                'phase_one_outcome' => 'Phase I outcome must be recorded before leaving Phase I.',
            ]);
        }

        if (
            $toStatus === LaboratoryOosStatus::Closed
            && $fromStatus === LaboratoryOosStatus::Confirmed
            && $event->investigation_id === null
            && $event->deviation_id === null
        ) {
            throw ValidationException::withMessages([
                'investigation_id' => 'A confirmed OOS/OOT must be linked to an investigation or deviation before closure.',
            ]);
        }

        if (
            $toStatus === LaboratoryOosStatus::InvalidationProposed
            && blank($event->invalidation_justification)
        ) {
            throw ValidationException::withMessages([
                'invalidation_justification' => 'An invalidation justification is required when proposing invalidation.',
            ]);
        }
    }

    private function requiresSignature(LaboratoryOosStatus $status): bool
    {
        return in_array($status, [
            LaboratoryOosStatus::Confirmed,
            LaboratoryOosStatus::Closed,
            LaboratoryOosStatus::Cancelled,
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
