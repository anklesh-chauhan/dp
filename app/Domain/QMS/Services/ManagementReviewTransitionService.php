<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ManagementReviewInputType;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\Shared\Contracts\ElectronicSignatureHasher;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ManagementReviewTransitionService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ElectronicSignatureHasher $electronicSignatureHasher,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function transition(
        ManagementReview $review,
        ManagementReviewStatus $toStatus,
        User $actor,
        string $reason,
        ?string $inputSummary = null,
        ?string $decisions = null,
        ?string $actionSummary = null,
        array $context = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ManagementReview {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can($this->permissionFor($toStatus))) {
            throw new AuthorizationException('You do not have permission to perform this management review transition.');
        }

        if ($toStatus === ManagementReviewStatus::Completed
            && ! $actor->can('Approve:ManagementReview')) {
            throw new AuthorizationException('You do not have permission to approve this management review.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every management review transition.',
            ]);
        }

        return DB::transaction(function () use (
            $review,
            $toStatus,
            $actor,
            $normalizedReason,
            $inputSummary,
            $decisions,
            $actionSummary,
            $context,
            $ipAddress,
            $userAgent,
        ): ManagementReview {
            $record = ManagementReview::query()->lockForUpdate()->findOrFail($review->getKey());
            $fromStatus = $record->status;

            if (! in_array($toStatus, $this->allowedFrom($fromStatus), true)) {
                throw ValidationException::withMessages([
                    'status' => "Management review cannot transition from {$fromStatus->value} to {$toStatus->value}.",
                ]);
            }

            $updates = $this->validatedUpdates(
                $record,
                $toStatus,
                $actor,
                $inputSummary,
                $decisions,
                $actionSummary,
            );
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

            $record->update([
                'status' => $toStatus,
                ...$updates,
                ...($toStatus === ManagementReviewStatus::InProgress
                    && $record->held_at === null ? ['held_at' => $occurredAt] : []),
                ...($toStatus === ManagementReviewStatus::ActionsPending
                    ? ['minutes_issued_at' => $occurredAt] : []),
                ...($toStatus === ManagementReviewStatus::Completed ? [
                    'approved_by' => $actor->getKey(),
                    'approved_at' => $occurredAt,
                    'completed_at' => $occurredAt,
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

    /** @return list<ManagementReviewStatus> */
    private function allowedFrom(ManagementReviewStatus $status): array
    {
        return match ($status) {
            ManagementReviewStatus::Draft => [
                ManagementReviewStatus::Scheduled,
                ManagementReviewStatus::Cancelled,
            ],
            ManagementReviewStatus::Scheduled => [
                ManagementReviewStatus::InProgress,
                ManagementReviewStatus::Cancelled,
            ],
            ManagementReviewStatus::InProgress => [
                ManagementReviewStatus::MinutesPending,
                ManagementReviewStatus::Cancelled,
            ],
            ManagementReviewStatus::MinutesPending => [
                ManagementReviewStatus::ActionsPending,
                ManagementReviewStatus::Cancelled,
            ],
            ManagementReviewStatus::ActionsPending => [
                ManagementReviewStatus::Completed,
                ManagementReviewStatus::MinutesPending,
            ],
            ManagementReviewStatus::Completed,
            ManagementReviewStatus::Cancelled => [],
        };
    }

    private function permissionFor(ManagementReviewStatus $status): string
    {
        return match ($status) {
            ManagementReviewStatus::Scheduled => 'Schedule:ManagementReview',
            ManagementReviewStatus::InProgress => 'Conduct:ManagementReview',
            ManagementReviewStatus::MinutesPending => 'IssueMinutes:ManagementReview',
            ManagementReviewStatus::ActionsPending => 'IssueMinutes:ManagementReview',
            ManagementReviewStatus::Completed => 'Complete:ManagementReview',
            ManagementReviewStatus::Cancelled => 'Manage:ManagementReview',
            ManagementReviewStatus::Draft => 'Update:ManagementReview',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedUpdates(
        ManagementReview $review,
        ManagementReviewStatus $toStatus,
        User $actor,
        ?string $inputSummary,
        ?string $decisions,
        ?string $actionSummary,
    ): array {
        if ($toStatus === ManagementReviewStatus::Scheduled) {
            if ($review->period_end_at->lt($review->period_start_at)
                || $review->scheduled_at === null
                || $review->scheduled_at->lt($review->period_end_at->startOfDay())
                || $review->chair_id === null
                || $review->coordinator_id === null) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'A coherent review period, meeting date, chair, and coordinator are required.',
                ]);
            }

            $required = array_map(
                static fn (ManagementReviewInputType $type): string => $type->value,
                ManagementReviewInputType::cases(),
            );
            $selected = array_map(
                static fn (ManagementReviewInputType $type): string => $type->value,
                $review->requiredInputTypes(),
            );
            sort($required);
            sort($selected);

            if ($selected !== $required) {
                throw ValidationException::withMessages([
                    'required_inputs' => 'Every required quality-system input category must be included.',
                ]);
            }
        }

        if ($toStatus === ManagementReviewStatus::MinutesPending) {
            $normalizedInputSummary = trim((string) ($inputSummary ?? $review->input_summary));
            $normalizedDecisions = trim((string) ($decisions ?? $review->decisions));
            if ($normalizedInputSummary === '' || $normalizedDecisions === '') {
                throw ValidationException::withMessages([
                    'input_summary' => 'Reviewed inputs and resulting decisions are required before minutes.',
                ]);
            }

            return [
                'input_summary' => $normalizedInputSummary,
                'decisions' => $normalizedDecisions,
            ];
        }

        if ($toStatus === ManagementReviewStatus::ActionsPending) {
            $normalizedActionSummary = trim((string) ($actionSummary ?? $review->action_summary));
            if (blank($review->input_summary)
                || blank($review->decisions)
                || $normalizedActionSummary === '') {
                throw ValidationException::withMessages([
                    'action_summary' => 'Input, decision, and action outputs are required before issuing minutes.',
                ]);
            }

            return ['action_summary' => $normalizedActionSummary];
        }

        if ($toStatus === ManagementReviewStatus::Completed) {
            if ($review->minutes_issued_at === null
                || blank($review->input_summary)
                || blank($review->decisions)
                || blank($review->action_summary)) {
                throw ValidationException::withMessages([
                    'minutes_issued_at' => 'Issued minutes and complete review outputs are required.',
                ]);
            }

            if (in_array((int) $actor->getKey(), array_filter([
                $review->created_by,
                $review->coordinator_id,
            ]), true)) {
                throw ValidationException::withMessages([
                    'approved_by' => 'The creator or coordinator cannot independently approve completion.',
                ]);
            }
        }

        return [];
    }

    private function requiresSignature(ManagementReviewStatus $status): bool
    {
        return in_array($status, [
            ManagementReviewStatus::MinutesPending,
            ManagementReviewStatus::ActionsPending,
            ManagementReviewStatus::Completed,
            ManagementReviewStatus::Cancelled,
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
