<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuditFindingTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(
        AuditFinding $finding,
        AuditFindingDisposition $toDisposition,
        User $actor,
        string $reason,
        ?string $response = null,
        ?string $verificationNotes = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditFinding {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toDisposition))) {
            throw new AuthorizationException('You do not have permission to perform this audit finding transition.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every audit finding transition.',
            ]);
        }

        return DB::transaction(function () use (
            $finding,
            $toDisposition,
            $actor,
            $normalizedReason,
            $response,
            $verificationNotes,
            $context,
            $ipAddress,
            $userAgent,
        ): AuditFinding {
            $record = AuditFinding::query()->lockForUpdate()->findOrFail($finding->getKey());
            $fromDisposition = $record->disposition;

            if (! in_array($toDisposition, $this->allowedFrom($fromDisposition), true)) {
                throw ValidationException::withMessages([
                    'disposition' => "Audit finding cannot transition from {$fromDisposition->value} to {$toDisposition->value}.",
                ]);
            }

            $updates = $this->validatedUpdates(
                $record,
                $fromDisposition,
                $toDisposition,
                $actor,
                $response,
                $verificationNotes,
            );
            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $eventContext = $this->sanitize($context);

            if ($fromDisposition === AuditFindingDisposition::UnderVerification
                && $toDisposition === AuditFindingDisposition::ResponsePending) {
                $eventContext['verification_notes'] = $record->verification_notes;
            }

            $signatureHash = $this->requiresSignature($toDisposition, $fromDisposition)
                ? $this->electronicSignatureHasher->hashFor(
                    recordKey: $eventUuid,
                    meaning: $toDisposition->value,
                    signerId: $actor->getKey(),
                    signedAt: $occurredAt,
                    reason: $normalizedReason,
                    ipAddress: $ipAddress,
                    userAgent: $userAgent,
                )
                : null;

            $record->update([
                'disposition' => $toDisposition,
                ...$updates,
                ...($toDisposition === AuditFindingDisposition::UnderVerification
                    ? ['responded_at' => $occurredAt] : []),
                ...($toDisposition === AuditFindingDisposition::Accepted
                    ? ['verified_at' => $occurredAt] : []),
                ...($toDisposition === AuditFindingDisposition::Closed
                    ? ['closed_at' => $occurredAt] : []),
            ]);
            $record->auditEvents()->create([
                'event_uuid' => $eventUuid,
                'from_disposition' => $fromDisposition,
                'to_disposition' => $toDisposition,
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

    /** @return list<AuditFindingDisposition> */
    private function allowedFrom(AuditFindingDisposition $disposition): array
    {
        return match ($disposition) {
            AuditFindingDisposition::Open => [
                AuditFindingDisposition::ResponsePending,
                AuditFindingDisposition::Rejected,
                AuditFindingDisposition::Cancelled,
            ],
            AuditFindingDisposition::ResponsePending => [
                AuditFindingDisposition::UnderVerification,
                AuditFindingDisposition::Cancelled,
            ],
            AuditFindingDisposition::UnderVerification => [
                AuditFindingDisposition::Accepted,
                AuditFindingDisposition::ResponsePending,
                AuditFindingDisposition::Rejected,
            ],
            AuditFindingDisposition::Accepted => [AuditFindingDisposition::Closed],
            AuditFindingDisposition::Closed,
            AuditFindingDisposition::Rejected,
            AuditFindingDisposition::Cancelled => [],
        };
    }

    private function permissionFor(AuditFindingDisposition $disposition): string
    {
        return match ($disposition) {
            AuditFindingDisposition::ResponsePending,
            AuditFindingDisposition::UnderVerification => 'Respond:AuditFinding',
            AuditFindingDisposition::Accepted,
            AuditFindingDisposition::Rejected => 'Verify:AuditFinding',
            AuditFindingDisposition::Closed => 'Close:AuditFinding',
            AuditFindingDisposition::Cancelled => 'Manage:AuditFinding',
            AuditFindingDisposition::Open => 'Update:AuditFinding',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUpdates(
        AuditFinding $finding,
        AuditFindingDisposition $fromDisposition,
        AuditFindingDisposition $toDisposition,
        User $actor,
        ?string $response,
        ?string $verificationNotes,
    ): array {
        if ($toDisposition === AuditFindingDisposition::UnderVerification) {
            $normalizedResponse = trim((string) $response);
            if ($normalizedResponse === '') {
                throw ValidationException::withMessages([
                    'response' => 'A documented response is required for verification.',
                ]);
            }

            return ['response' => $normalizedResponse];
        }

        if (in_array($toDisposition, [
            AuditFindingDisposition::Accepted,
            AuditFindingDisposition::Rejected,
        ], true)) {
            if ($finding->owner_id !== null && (int) $finding->owner_id === (int) $actor->getKey()) {
                throw ValidationException::withMessages([
                    'verified_by' => 'The response owner cannot independently verify the finding.',
                ]);
            }

            $normalizedNotes = trim((string) $verificationNotes);
            if ($normalizedNotes === '') {
                throw ValidationException::withMessages([
                    'verification_notes' => 'Independent verification evidence is required.',
                ]);
            }

            return [
                'verified_by' => $actor->getKey(),
                'verification_notes' => $normalizedNotes,
            ];
        }

        if ($fromDisposition === AuditFindingDisposition::UnderVerification
            && $toDisposition === AuditFindingDisposition::ResponsePending) {
            return [
                'verified_by' => null,
                'verification_notes' => null,
                'verified_at' => null,
            ];
        }

        if ($toDisposition === AuditFindingDisposition::Closed
            && ($finding->verified_at === null || blank($finding->verification_notes))) {
            throw ValidationException::withMessages([
                'verified_at' => 'Accepted independent verification is required before closure.',
            ]);
        }

        return [];
    }

    private function requiresSignature(
        AuditFindingDisposition $toDisposition,
        AuditFindingDisposition $fromDisposition,
    ): bool {
        return $toDisposition === AuditFindingDisposition::UnderVerification
            || $fromDisposition === AuditFindingDisposition::UnderVerification
            || in_array($toDisposition, [
                AuditFindingDisposition::Closed,
                AuditFindingDisposition::Rejected,
                AuditFindingDisposition::Cancelled,
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
