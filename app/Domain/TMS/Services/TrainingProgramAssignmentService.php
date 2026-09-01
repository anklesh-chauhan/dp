<?php

declare(strict_types=1);

namespace App\Domain\TMS\Services;

use App\Domain\DMS\Services\DocumentTrainingService;
use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Domain\TMS\Models\TrainingProgram;
use App\Enums\ProductModule;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TrainingProgramAssignmentService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly DocumentTrainingService $documentTrainingService,
    ) {}

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, TrainingAssignment>
     */
    public function assign(TrainingProgram $program, User $actor, array $userIds): Collection
    {
        $this->moduleManager->ensureEnabled(ProductModule::TMS);

        if (! $actor->can('Assign:TrainingProgram')) {
            throw new AuthorizationException('You do not have permission to assign training programs.');
        }

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

        $created = collect();

        foreach ($userIds as $userId) {
            $trainee = $users->get($userId);

            if (! $trainee instanceof User) {
                continue;
            }

            $assignment = $this->assignProgramToTrainee($program, $trainee, $actor);

            if ($assignment->wasRecentlyCreated) {
                $created->push($assignment);
            }
        }

        if ($created->isEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'The selected people are already assigned to this training program.',
            ]);
        }

        return $created;
    }

    public function assignProgramToTrainee(
        TrainingProgram $program,
        User $trainee,
        User $actor,
    ): TrainingAssignment {
        $this->moduleManager->ensureEnabled(ProductModule::TMS);

        $program->loadMissing(['items.controlledDocument.documentStatus']);

        $approvedDocuments = $program->items
            ->filter(fn ($item): bool => (bool) $item->is_required)
            ->map(fn ($item) => $item->controlledDocument)
            ->filter(fn (?ControlledDocument $document): bool => $document?->documentStatus?->hasCode(DocumentStatus::APPROVED) ?? false)
            ->unique(fn (ControlledDocument $document): int => (int) $document->getKey())
            ->values();

        if ($approvedDocuments->isEmpty()) {
            throw ValidationException::withMessages([
                'program' => 'Add at least one approved required document before assigning trainees.',
            ]);
        }

        return DB::transaction(function () use ($program, $trainee, $actor, $approvedDocuments): TrainingAssignment {
            $programAssignment = TrainingAssignment::query()->firstOrCreate(
                [
                    'training_program_id' => $program->getKey(),
                    'user_id' => $trainee->getKey(),
                ],
                [
                    'source_type' => TrainingAssignmentSource::TrainingProgram,
                    'assigned_by' => $actor->getKey(),
                    'assigned_at' => now(),
                ],
            );

            foreach ($approvedDocuments as $document) {
                try {
                    $this->documentTrainingService->assign($document, $actor, [$trainee->getKey()]);
                } catch (ValidationException $exception) {
                    if (! collect($exception->errors())->flatten()->contains(
                        fn (mixed $message): bool => is_string($message) && str_contains($message, 'already assigned'),
                    )) {
                        throw $exception;
                    }
                }
            }

            return $programAssignment;
        });
    }
}
