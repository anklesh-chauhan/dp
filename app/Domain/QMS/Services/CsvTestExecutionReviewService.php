<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\CsvExecutionResult;
use App\Domain\QMS\Models\CsvSignedDecision;
use App\Domain\QMS\Models\CsvTestExecution;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CsvTestExecutionReviewService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    public function review(
        CsvTestExecution $execution,
        User $actor,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): CsvTestExecution {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Review:CsvValidationProject')) {
            throw new AuthorizationException('You do not have permission to review CSV test executions.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required.']);
        }

        return DB::transaction(function () use ($execution, $actor, $reason, $ipAddress, $userAgent): CsvTestExecution {
            $record = CsvTestExecution::query()->lockForUpdate()->findOrFail($execution->getKey());

            if ($record->reviewed_at !== null) {
                throw ValidationException::withMessages([
                    'reviewed_at' => 'This CSV test execution has already been reviewed.',
                ]);
            }

            if ($record->completed_at === null) {
                throw ValidationException::withMessages([
                    'completed_at' => 'An execution must be completed before independent review.',
                ]);
            }

            if ((int) $actor->getKey() === (int) $record->executed_by) {
                throw ValidationException::withMessages([
                    'reviewed_by' => 'The test reviewer must be different from the executor.',
                ]);
            }

            if (
                in_array($record->result, [CsvExecutionResult::Failed, CsvExecutionResult::Blocked], true)
                && $record->deviation_id === null
            ) {
                throw ValidationException::withMessages([
                    'deviation_id' => 'A failed or blocked execution must be linked to a deviation.',
                ]);
            }

            if (! $record->attachments()->exists()) {
                throw ValidationException::withMessages([
                    'attachments' => 'At least one quality evidence attachment is required before review.',
                ]);
            }

            $occurredAt = now();
            $decisionUuid = (string) Str::uuid();
            $normalizedReason = trim($reason);
            $fromState = $record->result?->value;
            $toState = 'reviewed';
            $signatureHash = $this->electronicSignatureHasher->issueFor(
                signer: $actor,
                recordKey: $decisionUuid,
                meaning: 'reviewed',
                signedAt: $occurredAt,
                reason: $normalizedReason,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
            );

            $record->update([
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => $occurredAt,
            ]);

            CsvSignedDecision::query()->create([
                'decision_uuid' => $decisionUuid,
                'csv_validation_project_id' => $record->csv_validation_project_id,
                'subject_type' => $record->getMorphClass(),
                'subject_id' => $record->getKey(),
                'decision_code' => 'reviewed',
                'from_state' => $fromState,
                'to_state' => $toState,
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $this->sanitize([
                    'execution_uuid' => $record->execution_uuid,
                    'execution_no' => $record->execution_no,
                    'result' => $record->result?->value,
                    'deviation_id' => $record->deviation_id,
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
