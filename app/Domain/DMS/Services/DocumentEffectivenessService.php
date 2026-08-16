<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Services\WorkflowNotificationService;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\SopAuditLog;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentEffectivenessService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly DocumentActivationService $documentActivationService,
        private readonly DocumentTrainingService $documentTrainingService,
        private readonly WorkflowNotificationService $workflowNotificationService,
    ) {}

    public function release(
        ControlledDocument $document,
        User $actor,
        CarbonInterface|string $effectiveDate,
        ?string $reason = null,
    ): ControlledDocument {
        $effectiveDateString = $this->normalizedEffectiveDate($effectiveDate);
        $reason = trim((string) $reason);

        return DB::transaction(function () use ($document, $actor, $effectiveDateString, $reason): ControlledDocument {
            $releasingDocument = ControlledDocument::query()
                ->with(['documentStatus', 'documentType', 'trainingAssignments'])
                ->lockForUpdate()
                ->findOrFail($document->id);

            if (! $releasingDocument->documentStatus?->hasCode(DocumentStatus::APPROVED)) {
                throw ValidationException::withMessages([
                    'document' => 'Only approved documents can be made effective.',
                ]);
            }

            if (! $this->documentTrainingService->isSatisfied($releasingDocument)) {
                throw ValidationException::withMessages([
                    'document' => $this->unsatisfiedTrainingMessage($releasingDocument),
                ]);
            }

            $releasingDocument->update([
                'effective_date' => $effectiveDateString,
                'released_for_effectiveness_at' => now(),
                'released_for_effectiveness_by' => $actor->id,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_EFFECTIVENESS_RELEASED,
                newValues: [
                    'effective_date' => $effectiveDateString,
                    'reason' => $reason !== '' ? $reason : null,
                ],
                userId: $actor->id,
                document: $releasingDocument,
            );

            $activated = $this->activateIfDue($releasingDocument->refresh(), $actor);

            if ($activated->documentStatus?->hasCode(DocumentStatus::EFFECTIVE)) {
                $this->workflowNotificationService->notifyDocumentMadeEffective($activated, $actor);
            } else {
                $this->workflowNotificationService->notifyDocumentEffectivenessScheduled($activated, $actor);
            }

            return $activated;
        });
    }

    public function activateDueDocuments(): int
    {
        $dueDocuments = ControlledDocument::query()
            ->with(['documentStatus', 'releasedForEffectivenessBy', 'owner', 'creator'])
            ->whereHas(
                'documentStatus',
                fn ($query) => $query->where('code', DocumentStatus::APPROVED)
            )
            ->whereNotNull('released_for_effectiveness_at')
            ->whereDate('effective_date', '<=', now()->toDateString())
            ->orderBy('id')
            ->get();

        $activated = 0;

        foreach ($dueDocuments as $document) {
            $actor = $document->releasedForEffectivenessBy
                ?? $document->owner
                ?? $document->creator;

            if (! $actor instanceof User) {
                continue;
            }

            $result = $this->activateIfDue($document, $actor);

            if ($result->documentStatus?->hasCode(DocumentStatus::EFFECTIVE)) {
                $this->workflowNotificationService->notifyDocumentMadeEffective($result, $actor);
                $activated++;
            }
        }

        return $activated;
    }

    private function activateIfDue(ControlledDocument $document, User $actor): ControlledDocument
    {
        $document->loadMissing('documentStatus');

        if (! $document->documentStatus?->hasCode(DocumentStatus::APPROVED)) {
            return $document;
        }

        if ($document->effective_date === null || $document->effective_date->toDateString() > now()->toDateString()) {
            return $document;
        }

        return $this->documentActivationService->activate($document, $actor);
    }

    private function normalizedEffectiveDate(CarbonInterface|string $effectiveDate): string
    {
        $date = $effectiveDate instanceof CarbonInterface
            ? $effectiveDate->toDateString()
            : (string) $effectiveDate;

        if ($date === '') {
            throw ValidationException::withMessages([
                'effective_date' => 'An effective date is required.',
            ]);
        }

        if ($date < now()->toDateString()) {
            throw ValidationException::withMessages([
                'effective_date' => 'The effective date cannot be earlier than today.',
            ]);
        }

        return $date;
    }

    private function unsatisfiedTrainingMessage(ControlledDocument $document): string
    {
        $assignments = $document->trainingAssignments;

        if ($assignments->isEmpty()) {
            return 'Required training must be assigned and completed before this document can be made effective.';
        }

        return 'All required training must be completed before this document can be made effective.';
    }
}
