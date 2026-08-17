<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Models\CsvRiskAssessment;
use App\Domain\QMS\Models\CsvSignedDecision;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CsvRiskAcceptanceService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    public function accept(
        CsvRiskAssessment $risk,
        User $actor,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): CsvRiskAssessment {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Review:CsvValidationProject')) {
            throw new AuthorizationException('You do not have permission to accept CSV residual risks.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required.']);
        }

        return DB::transaction(function () use ($risk, $actor, $reason, $ipAddress, $userAgent): CsvRiskAssessment {
            $record = CsvRiskAssessment::query()->lockForUpdate()->findOrFail($risk->getKey());

            if ($record->accepted_at !== null) {
                throw ValidationException::withMessages([
                    'accepted_at' => 'This CSV risk assessment has already been accepted.',
                ]);
            }

            $residualRpn = $record->residualRiskPriorityNumber();
            if ($residualRpn === null) {
                throw ValidationException::withMessages([
                    'residual_severity' => 'Residual severity, probability, and detectability are required before acceptance.',
                ]);
            }

            if ($residualRpn > $record->initialRiskPriorityNumber()) {
                throw ValidationException::withMessages([
                    'residual_severity' => 'Accepted residual risk must not exceed the initial risk priority number.',
                ]);
            }

            $occurredAt = now();
            $decisionUuid = (string) Str::uuid();
            $normalizedReason = trim($reason);
            $signatureHash = $this->electronicSignatureHasher->hashFor(
                recordKey: $decisionUuid,
                meaning: 'accepted',
                signerId: $actor->getKey(),
                signedAt: $occurredAt,
                reason: $normalizedReason,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            $record->update([
                'accepted_by' => $actor->getKey(),
                'accepted_at' => $occurredAt,
                'acceptance_rationale' => filled($record->acceptance_rationale)
                    ? $record->acceptance_rationale
                    : $normalizedReason,
            ]);

            CsvSignedDecision::query()->create([
                'decision_uuid' => $decisionUuid,
                'csv_validation_project_id' => $record->csv_validation_project_id,
                'subject_type' => $record->getMorphClass(),
                'subject_id' => $record->getKey(),
                'decision_code' => 'accepted',
                'from_state' => null,
                'to_state' => 'accepted',
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $this->sanitize([
                    'risk_identifier' => $record->risk_identifier,
                    'initial_rpn' => $record->initialRiskPriorityNumber(),
                    'residual_rpn' => $residualRpn,
                ]),
                'signature_hash' => $signatureHash,
                'signature_ip_address' => $ipAddress,
                'signature_user_agent' => $userAgent,
                'occurred_at' => $occurredAt,
            ]);

            return $record->refresh();
        });
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
