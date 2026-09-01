<?php

declare(strict_types=1);

namespace App\Domain\TMS\Services;

use App\Domain\DMS\Services\DocumentTrainingService;
use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Enums\ProductModule;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DocumentRetrainingService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly DocumentTrainingService $documentTrainingService,
    ) {}

    public function scheduleForNewlyApprovedRevision(ControlledDocument $document, User $actor): void
    {
        if (! $this->moduleManager->enabled(ProductModule::TMS)) {
            return;
        }

        $document->loadMissing(['documentStatus', 'supersedesDocument']);

        if (! $document->documentStatus?->hasCode(DocumentStatus::APPROVED)) {
            return;
        }

        $superseded = $document->supersedesDocument;

        if (! $superseded instanceof ControlledDocument) {
            return;
        }

        $traineeIds = TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->where('controlled_document_id', $superseded->getKey())
            ->whereNotNull('completed_at')
            ->pluck('user_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($traineeIds === []) {
            return;
        }

        DB::transaction(function () use ($document, $actor, $traineeIds): void {
            foreach (array_chunk($traineeIds, 50) as $chunk) {
                try {
                    $this->documentTrainingService->assign($document, $actor, $chunk);
                } catch (ValidationException) {
                    // Idempotent when retraining was already scheduled.
                }
            }
        });
    }

    /**
     * @return Collection<int, TrainingAssignment>
     */
    public function pendingRetrainingForDocument(ControlledDocument $document): Collection
    {
        return TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->where('controlled_document_id', $document->getKey())
            ->whereNull('completed_at')
            ->with('user')
            ->get();
    }
}
