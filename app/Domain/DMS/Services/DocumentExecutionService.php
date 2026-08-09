<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Models\ControlledDocumentSection;
use App\Models\DocumentExecution;
use App\Models\DocumentExecutionSection;
use App\Models\DocumentIssuance;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentExecutionService
{
    /**
     * @param  array{batch_number?: string|null, product_name?: string|null, log_frequency?: string|null, log_period_start?: string|null, log_period_end?: string|null, supervisor_id?: int|null}  $data
     */
    public function initialize(DocumentIssuance $issuance, array $data = []): DocumentExecution
    {
        if (! $issuance->isExecution()) {
            throw ValidationException::withMessages(['issuance_type' => 'Only an execution issuance can create a writable GMP record.']);
        }

        return DB::transaction(function () use ($issuance, $data): DocumentExecution {
            $issuance->loadMissing('document.documentType', 'document.sections.items');
            $document = $issuance->document;
            $workflow = $document->documentType?->resolvedExecutionWorkflow() ?? [];

            $execution = DocumentExecution::query()->create([
                'document_issuance_id' => $issuance->id,
                'execution_number' => 'REC-'.$issuance->issuance_number,
                'document_number' => $document->document_number,
                'document_version' => $document->version,
                'document_type_code' => $document->documentType?->code ?? 'FORM',
                'workflow_configuration' => $workflow,
                'batch_number' => $data['batch_number'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'log_frequency' => $data['log_frequency'] ?? null,
                'log_period_start' => $data['log_period_start'] ?? null,
                'log_period_end' => $data['log_period_end'] ?? null,
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'disposition' => ($workflow['requires_disposition'] ?? false)
                    ? DocumentExecution::DISPOSITION_PENDING
                    : DocumentExecution::DISPOSITION_NOT_APPLICABLE,
            ]);

            foreach ($document->sections as $masterSection) {
                $executionSection = $execution->sections()->create([
                    'source_section_id' => $masterSection->id,
                    'title' => $masterSection->title,
                    'section_order' => $masterSection->section_order,
                    'section_type' => $masterSection->section_type,
                    'content' => $masterSection->content,
                    'configuration' => $masterSection->configuration,
                ]);

                $rowCount = $masterSection->requiresFieldDefinitions()
                    ? min(100, max(1, (int) data_get($masterSection->configuration, 'execution_row_count', 1)))
                    : 1;

                for ($rowNumber = 1; $rowNumber <= $rowCount; $rowNumber++) {
                    foreach ($masterSection->items as $masterItem) {
                        $executionSection->items()->create($masterItem->only([
                            'item_order', 'label', 'value_type', 'unit', 'decimal_precision',
                            'acceptance_operator', 'acceptance_min', 'acceptance_max', 'is_required',
                        ]) + [
                            'source_item_id' => $masterItem->id,
                            'row_number' => $rowNumber,
                        ]);
                    }
                }

                if ($masterSection->section_type === ControlledDocumentSection::TYPE_REPEATING_LOG
                    && ! $executionSection->items()->exists()) {
                    $this->generateScheduledEntries($execution, $executionSection);
                }
            }

            return $execution->refresh();
        });
    }

    public function begin(DocumentExecution $execution): DocumentExecution
    {
        $this->ensureStatus($execution, [DocumentExecution::STATUS_ISSUED]);
        $execution->update(['status' => DocumentExecution::STATUS_IN_PROGRESS]);

        return $execution->refresh();
    }

    public function complete(DocumentExecution $execution, User $user): DocumentExecution
    {
        $this->ensureStatus($execution, [DocumentExecution::STATUS_ISSUED, DocumentExecution::STATUS_IN_PROGRESS]);
        $execution->load('sections.items');

        $incompleteSections = $execution->sections
            ->filter(fn (DocumentExecutionSection $section): bool => ! $section->isCompleted())
            ->pluck('title');
        $missingItems = $execution->sections->flatMap(fn (DocumentExecutionSection $section) => $section->items
            ->filter(fn ($item): bool => $item->is_required && blank($item->response))
            ->map(fn ($item): string => "{$section->title}: {$item->label}"));

        if ($incompleteSections->isNotEmpty() || $missingItems->isNotEmpty()) {
            $issues = collect()
                ->when($incompleteSections->isNotEmpty(), fn ($messages) => $messages->push('Complete these sections: '.$incompleteSections->implode(', ').'.'))
                ->when($missingItems->isNotEmpty(), fn ($messages) => $messages->push('Enter responses for: '.$missingItems->implode(', ').'.'));

            throw ValidationException::withMessages(['execution' => $issues->implode("\n")]);
        }

        if ($execution->requires('requires_item_verification')) {
            $unverifiedItems = $execution->sections->flatMap(fn (DocumentExecutionSection $section) => $section->items
                ->filter(fn ($item): bool => $item->is_required && ! $item->isIndependentlyVerified())
                ->map(fn ($item): string => "{$section->title}: {$item->label}"));

            if ($unverifiedItems->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'execution' => 'Independent verification is required for: '.$unverifiedItems->implode(', ').'.',
                ]);
            }
        }

        $nextStatus = $execution->requires('requires_supervisor_review')
            ? DocumentExecution::STATUS_UNDER_REVIEW
            : ($execution->requires('requires_qa_approval') ? DocumentExecution::STATUS_QA_REVIEW : DocumentExecution::STATUS_CLOSED);

        $execution->update([
            'status' => $nextStatus,
            'completed_by' => $user->id,
            'completed_at' => now(),
            'closed_at' => $nextStatus === DocumentExecution::STATUS_CLOSED ? now() : null,
        ]);

        return $execution->refresh();
    }

    public function review(DocumentExecution $execution, User $reviewer, ?string $notes = null): DocumentExecution
    {
        $this->ensureStatus($execution, [DocumentExecution::STATUS_UNDER_REVIEW]);

        if ($execution->completed_by === $reviewer->id) {
            throw ValidationException::withMessages(['reviewer' => 'The reviewer must be different from the person who completed the record.']);
        }

        $nextStatus = $execution->requires('requires_qa_approval')
            ? DocumentExecution::STATUS_QA_REVIEW
            : DocumentExecution::STATUS_CLOSED;

        $execution->update([
            'status' => $nextStatus,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'review_notes' => $notes,
            'closed_at' => $nextStatus === DocumentExecution::STATUS_CLOSED ? now() : null,
        ]);

        return $execution->refresh();
    }

    public function qaApprove(DocumentExecution $execution, User $approver, string $disposition, ?string $notes = null): DocumentExecution
    {
        $this->ensureStatus($execution, [DocumentExecution::STATUS_QA_REVIEW]);

        if ($execution->requires('requires_disposition') && ! $execution->materialsAreReconciled()) {
            throw ValidationException::withMessages([
                'materials' => 'Batch materials and quantities must be entered and reconciled before QA disposition.',
            ]);
        }

        if (! in_array($disposition, [DocumentExecution::DISPOSITION_RELEASED, DocumentExecution::DISPOSITION_REJECTED], true)) {
            throw ValidationException::withMessages(['disposition' => 'Select Released or Rejected.']);
        }

        if (in_array($approver->id, [$execution->completed_by, $execution->reviewed_by], true)) {
            throw ValidationException::withMessages(['approver' => 'The QA approver must be independent of execution and production review.']);
        }

        $execution->update([
            'status' => DocumentExecution::STATUS_CLOSED,
            'qa_approved_by' => $approver->id,
            'qa_approved_at' => now(),
            'qa_notes' => $notes,
            'disposition' => $disposition,
            'closed_at' => now(),
        ]);

        return $execution->refresh();
    }

    /** @param array<int, string> $statuses */
    private function ensureStatus(DocumentExecution $execution, array $statuses): void
    {
        if (! in_array($execution->status, $statuses, true)) {
            throw ValidationException::withMessages(['execution' => 'This action is not allowed in the current execution status.']);
        }
    }

    private function generateScheduledEntries(DocumentExecution $execution, DocumentExecutionSection $section): void
    {
        if ($execution->log_period_start === null || $execution->log_period_end === null) {
            return;
        }

        $interval = match ($execution->log_frequency) {
            'hourly' => '1 hour',
            'shift' => '8 hours',
            'daily' => '1 day',
            default => null,
        };

        if ($interval === null) {
            return;
        }

        $end = in_array($execution->log_frequency, ['hourly', 'shift'], true)
            ? $execution->log_period_end->copy()->endOfDay()
            : $execution->log_period_end;

        foreach (CarbonPeriod::create($execution->log_period_start, $interval, $end) as $order => $scheduledAt) {
            $section->items()->create([
                'item_order' => $order + 1,
                'row_number' => $order + 1,
                'scheduled_at' => $scheduledAt,
                'label' => 'Scheduled log entry',
                'value_type' => 'text',
                'is_required' => true,
            ]);
        }
    }
}
