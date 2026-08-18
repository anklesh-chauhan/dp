<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ComputerizedSystemIncidentStatus;
use App\Domain\QMS\Models\ComputerizedSystemIncident;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ComputerizedSystemIncidentTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(
        ComputerizedSystemIncident $incident,
        ComputerizedSystemIncidentStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ComputerizedSystemIncident {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this computerized system incident transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every computerized system incident transition.',
            ]);
        }

        return DB::transaction(function () use ($incident, $toStatus, $actor, $normalizedReason, $context, $ipAddress, $userAgent): ComputerizedSystemIncident {
            $record = ComputerizedSystemIncident::query()->lockForUpdate()->findOrFail($incident->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Incident cannot transition from {$fromStatus->value} to {$toStatus->value}.",
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

            $milestones = match ($toStatus) {
                ComputerizedSystemIncidentStatus::Resolved => ['resolved_at' => $occurredAt],
                ComputerizedSystemIncidentStatus::Closed => ['closed_at' => $occurredAt],
                default => [],
            };

            $record->update([
                'status' => $toStatus,
                ...$milestones,
            ]);
            $record->auditEvents()->create([
                'event_uuid' => $eventUuid,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $context === [] ? null : $context,
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $signatureHash === null ? null : $ipAddress,
                'signature_user_agent' => $signatureHash === null ? null : $userAgent,
                'occurred_at' => $occurredAt,
            ]);

            return $record->refresh();
        });
    }

    /** @return list<ComputerizedSystemIncidentStatus> */
    private function allowedFrom(ComputerizedSystemIncidentStatus $status): array
    {
        return match ($status) {
            ComputerizedSystemIncidentStatus::Open => [
                ComputerizedSystemIncidentStatus::Investigating,
                ComputerizedSystemIncidentStatus::Cancelled,
            ],
            ComputerizedSystemIncidentStatus::Investigating => [
                ComputerizedSystemIncidentStatus::Resolved,
                ComputerizedSystemIncidentStatus::Cancelled,
            ],
            ComputerizedSystemIncidentStatus::Resolved => [
                ComputerizedSystemIncidentStatus::Closed,
                ComputerizedSystemIncidentStatus::Investigating,
            ],
            ComputerizedSystemIncidentStatus::Closed, ComputerizedSystemIncidentStatus::Cancelled => [],
        };
    }

    private function requiresSignature(ComputerizedSystemIncidentStatus $status): bool
    {
        return in_array($status, [
            ComputerizedSystemIncidentStatus::Resolved,
            ComputerizedSystemIncidentStatus::Closed,
            ComputerizedSystemIncidentStatus::Cancelled,
        ], true);
    }

    private function permissionFor(ComputerizedSystemIncidentStatus $status): string
    {
        return match ($status) {
            ComputerizedSystemIncidentStatus::Investigating => 'Investigate:ComputerizedSystemIncident',
            ComputerizedSystemIncidentStatus::Resolved, ComputerizedSystemIncidentStatus::Closed => 'Close:ComputerizedSystemIncident',
            ComputerizedSystemIncidentStatus::Cancelled => 'Manage:ComputerizedSystemIncident',
            default => 'Update:ComputerizedSystemIncident',
        };
    }
}
