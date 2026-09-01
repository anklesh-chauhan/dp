<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Models\ControlledDocument;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Support\Collection;

final class MyTrainingQueueService
{
    /**
     * @return Collection<int, TrainingAssignment>
     */
    public function pendingForUser(User $user): Collection
    {
        return TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->where('user_id', $user->getKey())
            ->whereNull('completed_at')
            ->with([
                'controlledDocument.documentStatus',
                'controlledDocument.documentType',
                'controlledDocument.department',
                'assignedBy',
            ])
            ->orderByDesc('assigned_at')
            ->get();
    }

    public function pendingCountForUser(User $user): int
    {
        return TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->where('user_id', $user->getKey())
            ->whereNull('completed_at')
            ->count();
    }

    public function documentViewUrl(ControlledDocument $document): string
    {
        $document->loadMissing('documentType');

        if (DocumentType::isRepeatingLogCode($document->documentType?->code)) {
            return LogDocumentResource::getUrl('view', ['record' => $document]);
        }

        return ControlledDocumentResource::getUrl('view', ['record' => $document]);
    }
}
