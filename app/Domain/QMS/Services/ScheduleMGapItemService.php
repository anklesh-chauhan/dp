<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\ScheduleMGapItem;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ScheduleMGapItemService
{
    public function __construct(private readonly ModuleManager $moduleManager) {}

    /**
     * @param  array{
     *     status?: ScheduleMGapItemStatus|string,
     *     evidence_notes?: string|null,
     *     evidence_document_id?: int|null,
     *     evidence_qms?: Model|null,
     *     owner_id?: int|null,
     *     due_at?: \DateTimeInterface|string|null,
     * }  $attributes
     * @param  array<string, mixed>  $context
     */
    public function updateStatus(
        ScheduleMGapItem $item,
        User $actor,
        string $reason,
        array $attributes,
        array $context = [],
    ): ScheduleMGapItem {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Conduct:ScheduleMGapAssessment') && ! $actor->can('Update:ScheduleMGapAssessment')) {
            throw new AuthorizationException('You do not have permission to update Schedule M gap items.');
        }

        $normalizedReason = trim($reason);
        if ($normalizedReason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required for every Schedule M gap item update.',
            ]);
        }

        return DB::transaction(function () use ($item, $actor, $normalizedReason, $attributes, $context): ScheduleMGapItem {
            $record = ScheduleMGapItem::query()->lockForUpdate()->findOrFail($item->getKey());
            $assessment = $record->assessment()->lockForUpdate()->firstOrFail();

            if (in_array($assessment->status, [
                ScheduleMGapAssessmentStatus::Approved,
                ScheduleMGapAssessmentStatus::Closed,
                ScheduleMGapAssessmentStatus::Cancelled,
            ], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Clause items cannot be updated after the assessment is approved, closed, or cancelled.',
                ]);
            }

            $fromStatus = $record->status;
            $toStatus = $this->resolveStatus($attributes['status'] ?? $fromStatus);

            $updates = [
                'status' => $toStatus,
            ];

            if (array_key_exists('evidence_notes', $attributes)) {
                $updates['evidence_notes'] = $attributes['evidence_notes'] === null
                    ? null
                    : trim((string) $attributes['evidence_notes']);
            }

            if (array_key_exists('evidence_document_id', $attributes)) {
                $updates['evidence_document_id'] = $attributes['evidence_document_id'];
            }

            if (array_key_exists('owner_id', $attributes)) {
                $updates['owner_id'] = $attributes['owner_id'];
            }

            if (array_key_exists('due_at', $attributes)) {
                $updates['due_at'] = $attributes['due_at'];
            }

            if (array_key_exists('evidence_qms', $attributes)) {
                $evidence = $attributes['evidence_qms'];
                if ($evidence instanceof Model) {
                    $updates['evidence_qms_type'] = $evidence->getMorphClass();
                    $updates['evidence_qms_id'] = $evidence->getKey();
                } else {
                    $updates['evidence_qms_type'] = null;
                    $updates['evidence_qms_id'] = null;
                }
            }

            if (in_array($toStatus, [
                ScheduleMGapItemStatus::Compliant,
                ScheduleMGapItemStatus::NotApplicable,
            ], true) && $record->closed_at === null) {
                $updates['closed_at'] = now();
            }

            if (in_array($toStatus, [
                ScheduleMGapItemStatus::NotAssessed,
                ScheduleMGapItemStatus::Partial,
                ScheduleMGapItemStatus::Gap,
            ], true)) {
                $updates['closed_at'] = null;
            }

            $record->update($updates);

            $assessment->auditEvents()->create([
                'event_uuid' => (string) Str::uuid(),
                'from_status' => $assessment->status,
                'to_status' => $assessment->status,
                'event_type' => 'item_update',
                'actor_id' => $actor->getKey(),
                'reason' => $normalizedReason,
                'context' => $this->sanitize([
                    ...$context,
                    'item_id' => $record->getKey(),
                    'clause_ref' => $record->clause_ref,
                    'from_item_status' => $fromStatus->value,
                    'to_item_status' => $toStatus->value,
                ]),
                'signature_hash' => null,
                'signature_ip_address' => null,
                'signature_user_agent' => null,
                'occurred_at' => now(),
            ]);

            return $record->refresh();
        });
    }

    private function resolveStatus(ScheduleMGapItemStatus|string $status): ScheduleMGapItemStatus
    {
        if ($status instanceof ScheduleMGapItemStatus) {
            return $status;
        }

        $resolved = ScheduleMGapItemStatus::tryFrom($status);
        if ($resolved === null) {
            throw ValidationException::withMessages([
                'status' => 'Invalid Schedule M gap item status.',
            ]);
        }

        return $resolved;
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
