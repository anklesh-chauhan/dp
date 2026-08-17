<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Models\CsvSignedDecision;
use App\Domain\QMS\Models\CsvTestCase;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CsvTestCaseApprovalService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    public function approve(
        CsvTestCase $testCase,
        User $actor,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): CsvTestCase {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Test:CsvValidationProject')) {
            throw new AuthorizationException('You do not have permission to approve CSV test cases.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required.']);
        }

        return DB::transaction(function () use ($testCase, $actor, $reason, $ipAddress, $userAgent): CsvTestCase {
            $record = CsvTestCase::query()->lockForUpdate()->findOrFail($testCase->getKey());

            if ($record->status !== CsvRequirementStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft CSV test cases can be approved.',
                ]);
            }

            $occurredAt = now();
            $decisionUuid = (string) Str::uuid();
            $normalizedReason = trim($reason);
            $fromState = $record->status->value;
            $toState = CsvRequirementStatus::Approved->value;
            $signatureHash = $this->electronicSignatureHasher->hashFor(
                recordKey: $decisionUuid,
                meaning: 'approved',
                signerId: $actor->getKey(),
                signedAt: $occurredAt,
                reason: $normalizedReason,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            $record->update([
                'status' => CsvRequirementStatus::Approved,
                'approved_by' => $actor->getKey(),
                'approved_at' => $occurredAt,
            ]);

            CsvSignedDecision::query()->create([
                'decision_uuid' => $decisionUuid,
                'csv_validation_project_id' => $record->csv_validation_project_id,
                'subject_type' => $record->getMorphClass(),
                'subject_id' => $record->getKey(),
                'decision_code' => 'approved',
                'from_state' => $fromState,
                'to_state' => $toState,
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $this->sanitize([
                    'test_identifier' => $record->test_identifier,
                    'version' => $record->version,
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
