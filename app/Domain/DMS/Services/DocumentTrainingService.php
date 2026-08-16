<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Shared\Services\AuditLogService;
use App\Domain\Shared\Services\WorkflowNotificationService;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentTrainingAssignment;
use App\Models\DocumentStatus;
use App\Models\SopAuditLog;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentTrainingService
{
    public function __construct(private readonly AuditLogService $auditLogService, private readonly WorkflowNotificationService $workflowNotificationService) {}

    public function requiresTraining(ControlledDocument $document): bool
    {
        $document->loadMissing('documentType');

        return $document->documentType?->requiresTrainingBeforeEffective() ?? true;
    }

    public function isSatisfied(ControlledDocument $document): bool
    {
        if (! $this->requiresTraining($document)) {
            return true;
        }

        $assignments = $this->assignments($document);

        if ($assignments->isEmpty()) {
            return false;
        }

        return $assignments->every(
            fn (ControlledDocumentTrainingAssignment $assignment): bool => $assignment->isCompleted()
        );
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, ControlledDocumentTrainingAssignment>
     */
    public function assign(ControlledDocument $document, User $actor, array $userIds): Collection
    {
        $this->ensureApproved($document);

        $userIds = collect($userIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Select at least one trainee.',
            ]);
        }

        $users = User::query()->whereKey($userIds->all())->get()->keyBy('id');

        if ($users->count() !== $userIds->count()) {
            throw ValidationException::withMessages([
                'user_ids' => 'One or more selected trainees could not be found.',
            ]);
        }

        return DB::transaction(function () use ($document, $actor, $userIds, $users): Collection {
            $existingUserIds = $document->trainingAssignments()
                ->whereIn('user_id', $userIds->all())
                ->pluck('user_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

            $created = collect();

            foreach ($userIds as $userId) {
                if (in_array($userId, $existingUserIds, true)) {
                    continue;
                }

                $created->push($document->trainingAssignments()->create([
                    'user_id' => $userId,
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                ]));
            }

            if ($created->isEmpty()) {
                throw ValidationException::withMessages([
                    'user_ids' => 'The selected people are already assigned to this document.',
                ]);
            }

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_TRAINING_ASSIGNED,
                newValues: [
                    'user_ids' => $created->pluck('user_id')->all(),
                    'assigned_names' => $created
                        ->map(fn (ControlledDocumentTrainingAssignment $assignment): string => (string) $users->get($assignment->user_id)?->name)
                        ->filter()
                        ->values()
                        ->all(),
                ],
                userId: $actor->id,
                document: $document,
            );

            foreach ($created as $assignment) {
                $trainee = $users->get($assignment->user_id);

                if ($trainee instanceof User) {
                    $this->workflowNotificationService->notifyDocumentTrainingAssigned($document, $trainee, $actor);
                }
            }

            return $created;
        });
    }

    public function complete(
        ControlledDocumentTrainingAssignment $assignment,
        User $actor,
        ?string $comments = null,
    ): ControlledDocumentTrainingAssignment {
        $assignment->loadMissing('document.documentStatus');
        $document = $assignment->document;

        if (! $document instanceof ControlledDocument) {
            throw ValidationException::withMessages([
                'assignment' => 'This training assignment is not linked to a controlled document.',
            ]);
        }

        $this->ensureApproved($document);

        if ((int) $assignment->user_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'assignment' => 'Only the assigned trainee can complete this training.',
            ]);
        }

        if ($assignment->isCompleted()) {
            throw ValidationException::withMessages([
                'assignment' => 'This training assignment is already complete.',
            ]);
        }

        $comments = trim((string) $comments);

        return DB::transaction(function () use ($assignment, $actor, $document, $comments): ControlledDocumentTrainingAssignment {
            $assignment->update([
                'completed_at' => now(),
                'completion_comments' => $comments !== '' ? $comments : null,
            ]);

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_TRAINING_COMPLETED,
                newValues: [
                    'assignment_id' => $assignment->id,
                    'user_id' => $actor->id,
                    'comments' => $comments !== '' ? $comments : null,
                ],
                userId: $actor->id,
                document: $document,
            );

            return $assignment->refresh();
        });
    }

    public function remove(ControlledDocumentTrainingAssignment $assignment, User $actor): void
    {
        $assignment->loadMissing('document.documentStatus');
        $document = $assignment->document;

        if (! $document instanceof ControlledDocument) {
            throw ValidationException::withMessages([
                'assignment' => 'This training assignment is not linked to a controlled document.',
            ]);
        }

        $this->ensureApproved($document);

        if ($assignment->isCompleted()) {
            throw ValidationException::withMessages([
                'assignment' => 'Completed training records cannot be removed.',
            ]);
        }

        DB::transaction(function () use ($assignment, $actor, $document): void {
            $assignment->delete();

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_TRAINING_REMOVED,
                newValues: [
                    'assignment_id' => $assignment->id,
                    'user_id' => $assignment->user_id,
                ],
                userId: $actor->id,
                document: $document,
            );
        });
    }

    /**
     * @return Collection<int, ControlledDocumentTrainingAssignment>
     */
    private function assignments(ControlledDocument $document): Collection
    {
        if ($document->relationLoaded('trainingAssignments')) {
            return $document->trainingAssignments;
        }

        return $document->trainingAssignments()->get();
    }

    private function ensureApproved(ControlledDocument $document): void
    {
        $document->loadMissing('documentStatus');

        if (! $document->documentStatus?->hasCode(DocumentStatus::APPROVED)) {
            throw ValidationException::withMessages([
                'document' => 'Training can only be assigned or completed after the document is approved.',
            ]);
        }
    }
}
