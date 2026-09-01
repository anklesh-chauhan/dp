<?php

declare(strict_types=1);

namespace App\Domain\TMS\Services;

use App\Domain\TMS\Models\RoleTrainingRequirement;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Domain\TMS\Models\TrainingProgram;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class DesignationTrainingAssignmentService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly TrainingProgramAssignmentService $trainingProgramAssignmentService,
    ) {}

    /**
     * @return Collection<int, TrainingAssignment>
     */
    public function syncForUser(User $trainee, User $actor): Collection
    {
        if (! $this->moduleManager->enabled(ProductModule::TMS)) {
            return collect();
        }

        if ($trainee->designation_id === null) {
            return collect();
        }

        $requirements = RoleTrainingRequirement::query()
            ->where('designation_id', $trainee->designation_id)
            ->where('is_required', true)
            ->whereHas('trainingProgram', fn ($query) => $query->where('is_active', true))
            ->with(['trainingProgram.items.controlledDocument.documentStatus'])
            ->get();

        $created = collect();

        foreach ($requirements as $requirement) {
            $program = $requirement->trainingProgram;

            if (! $program instanceof TrainingProgram) {
                continue;
            }

            try {
                $assignment = $this->trainingProgramAssignmentService->assignProgramToTrainee(
                    $program,
                    $trainee,
                    $actor,
                );

                if ($assignment->wasRecentlyCreated) {
                    $created->push($assignment);
                }
            } catch (ValidationException) {
                // Skip programs that are not ready for assignment, such as missing approved documents.
            }
        }

        return $created;
    }
}
