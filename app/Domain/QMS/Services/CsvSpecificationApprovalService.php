<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Models\CsvSignedDecision;
use App\Domain\QMS\Models\CsvSpecification;
use App\Domain\Shared\Services\ContentBoundElectronicSignatureIssuer;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CsvSpecificationApprovalService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ContentBoundElectronicSignatureIssuer $contentBoundSignatures,
    ) {}

    public function approve(
        CsvSpecification $specification,
        User $actor,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): CsvSpecification {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Specify:CsvValidationProject')) {
            throw new AuthorizationException('You do not have permission to approve CSV specifications.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required.']);
        }

        return DB::transaction(function () use ($specification, $actor, $reason, $ipAddress, $userAgent): CsvSpecification {
            $record = CsvSpecification::query()->lockForUpdate()->findOrFail($specification->getKey());

            if ($record->status !== CsvRequirementStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Only draft CSV specifications can be approved.',
                ]);
            }

            $occurredAt = now();
            $decisionUuid = (string) Str::uuid();
            $normalizedReason = trim($reason);
            $fromState = $record->status->value;
            $toState = CsvRequirementStatus::Approved->value;
            $eventContext = $this->sanitize([
                'specification_identifier' => $record->specification_identifier,
                'version' => $record->version,
            ]);
            [$signatureHash, $eventContext] = $this->contentBoundSignatures->issue(
                signer: $actor,
                subject: $record,
                recordKey: $decisionUuid,
                meaning: 'approved',
                signedAt: $occurredAt,
                reason: $normalizedReason,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                context: $eventContext,
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
                'context' => $eventContext,
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
