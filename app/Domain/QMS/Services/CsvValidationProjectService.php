<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvExecutionResult;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CsvValidationProjectService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(
        CsvValidationProject $project,
        CsvValidationProjectStatus $toStatus,
        User $actor,
        string $reason,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): CsvValidationProject {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this CSV validation transition.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'A reason is required.']);
        }

        return DB::transaction(function () use (
            $project, $toStatus, $actor, $reason, $context, $ipAddress, $userAgent,
        ): CsvValidationProject {
            $record = CsvValidationProject::query()
                ->with(['requirements.testCases.executions', 'risks', 'specifications'])
                ->lockForUpdate()
                ->findOrFail($project->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "CSV validation cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            if ($toStatus === CsvValidationProjectStatus::Released) {
                $this->validateRelease($record, $actor);
            }

            $occurredAt = now();
            $eventUuid = (string) Str::uuid();
            $normalizedReason = trim($reason);
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
                ...($toStatus === CsvValidationProjectStatus::Released ? [
                    'released_by' => $actor->getKey(),
                    'released_at' => $occurredAt,
                ] : []),
                ...($toStatus === CsvValidationProjectStatus::Retired ? [
                    'retired_at' => $occurredAt,
                ] : []),
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

    /** @return list<CsvValidationProjectStatus> */
    private function allowedFrom(CsvValidationProjectStatus $status): array
    {
        return match ($status) {
            CsvValidationProjectStatus::Draft => [CsvValidationProjectStatus::GxpAssessment, CsvValidationProjectStatus::Cancelled],
            CsvValidationProjectStatus::GxpAssessment => [CsvValidationProjectStatus::Planning, CsvValidationProjectStatus::Draft],
            CsvValidationProjectStatus::Planning => [CsvValidationProjectStatus::Specification, CsvValidationProjectStatus::GxpAssessment],
            CsvValidationProjectStatus::Specification => [CsvValidationProjectStatus::Testing, CsvValidationProjectStatus::Planning],
            CsvValidationProjectStatus::Testing => [CsvValidationProjectStatus::DeviationResolution, CsvValidationProjectStatus::ValidationReview],
            CsvValidationProjectStatus::DeviationResolution => [CsvValidationProjectStatus::Testing, CsvValidationProjectStatus::ValidationReview],
            CsvValidationProjectStatus::ValidationReview => [CsvValidationProjectStatus::Released, CsvValidationProjectStatus::Testing],
            CsvValidationProjectStatus::Released => [CsvValidationProjectStatus::PeriodicReview, CsvValidationProjectStatus::Retired],
            CsvValidationProjectStatus::PeriodicReview => [CsvValidationProjectStatus::Released, CsvValidationProjectStatus::Testing, CsvValidationProjectStatus::Retired],
            CsvValidationProjectStatus::Retired, CsvValidationProjectStatus::Cancelled => [],
        };
    }

    private function permissionFor(CsvValidationProjectStatus $status): string
    {
        return match ($status) {
            CsvValidationProjectStatus::GxpAssessment => 'Assess:CsvValidationProject',
            CsvValidationProjectStatus::Planning => 'Plan:CsvValidationProject',
            CsvValidationProjectStatus::Specification => 'Specify:CsvValidationProject',
            CsvValidationProjectStatus::Testing, CsvValidationProjectStatus::DeviationResolution => 'Test:CsvValidationProject',
            CsvValidationProjectStatus::ValidationReview => 'Review:CsvValidationProject',
            CsvValidationProjectStatus::Released => 'Release:CsvValidationProject',
            CsvValidationProjectStatus::PeriodicReview => 'PeriodicReview:CsvValidationProject',
            CsvValidationProjectStatus::Retired, CsvValidationProjectStatus::Cancelled, CsvValidationProjectStatus::Draft => 'Manage:CsvValidationProject',
        };
    }

    private function validateRelease(CsvValidationProject $project, User $actor): void
    {
        $errors = [];

        foreach ([
            'validation_strategy' => 'A validation strategy is required.',
            'release_baseline' => 'A release baseline is required.',
            'validation_summary' => 'A validation summary is required.',
            'next_periodic_review_date' => 'The next periodic review date is required.',
        ] as $attribute => $message) {
            if (blank($project->{$attribute})) {
                $errors[$attribute] = $message;
            }
        }

        if (in_array((int) $actor->getKey(), array_filter([
            $project->created_by,
            $project->business_owner_id,
            $project->system_owner_id,
        ]), true)) {
            $errors['released_by'] = 'QA release must be signed by a user independent of the creator and system/business owners.';
        }

        if ($project->requirements->isEmpty()) {
            $errors['requirements'] = 'At least one approved requirement is required.';
        }

        foreach ($project->requirements as $requirement) {
            if ($requirement->status !== CsvRequirementStatus::Approved) {
                $errors["requirements.{$requirement->id}"] = "{$requirement->requirement_identifier} is not approved.";

                continue;
            }

            $requiresTest = $requirement->gxp_relevant
                || in_array($requirement->criticality, [CsvCriticality::High, CsvCriticality::Critical], true);
            if (! $requiresTest) {
                continue;
            }

            $hasPassingReviewedTest = $requirement->testCases
                ->where('status', CsvRequirementStatus::Approved)
                ->contains(fn ($testCase): bool => $testCase->executions
                    ->sortByDesc('execution_no')
                    ->take(1)
                    ->contains(fn ($execution): bool => $execution->result === CsvExecutionResult::Passed
                        && $execution->reviewed_at !== null
                        && $execution->reviewed_by !== $execution->executed_by));

            if (! $hasPassingReviewedTest) {
                $errors["traceability.{$requirement->id}"] =
                    "{$requirement->requirement_identifier} needs a linked, approved, independently reviewed passing test.";
            }
        }

        foreach ($project->risks as $risk) {
            $residualRpn = $risk->residualRiskPriorityNumber();
            if ($risk->accepted_at === null
                || $risk->accepted_by === null
                || $residualRpn === null
                || $residualRpn > $risk->initialRiskPriorityNumber()) {
                $errors["risks.{$risk->id}"] = "{$risk->risk_identifier} requires documented, accepted residual risk not exceeding initial risk.";
            }
        }

        if ($project->specifications->contains(fn ($specification): bool => $specification->status !== CsvRequirementStatus::Approved)) {
            $errors['specifications'] = 'All specifications must be approved.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function requiresSignature(CsvValidationProjectStatus $status): bool
    {
        return in_array($status, [
            CsvValidationProjectStatus::ValidationReview,
            CsvValidationProjectStatus::Released,
            CsvValidationProjectStatus::PeriodicReview,
            CsvValidationProjectStatus::Retired,
            CsvValidationProjectStatus::Cancelled,
        ], true);
    }

    /** @param array<string, mixed> $context */
    private function sanitize(array $context): array
    {
        unset($context['signature'], $context['payload']);

        return $context;
    }
}
