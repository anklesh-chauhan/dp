<?php

declare(strict_types=1);

namespace App\Domain\TMS\Services;

use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\RoleTrainingRequirement;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Domain\TMS\Models\TrainingProgram;
use App\Domain\TMS\Models\TrainingProgramItem;
use App\Enums\ProductModule;
use App\Models\Designation;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Collection;

final class RoleTrainingMatrixService
{
    public function __construct(private readonly ModuleManager $moduleManager) {}

    /**
     * @return Collection<int, array{
     *     designation_code: string,
     *     designation_name: string,
     *     program_code: string,
     *     program_name: string,
     *     document_number: string,
     *     document_title: string,
     *     user_id: int,
     *     user_name: string,
     *     assignment_status: string
     * }>
     */
    public function rows(): Collection
    {
        if (! $this->moduleManager->enabled(ProductModule::TMS)) {
            return collect();
        }

        $requirements = RoleTrainingRequirement::query()
            ->with([
                'designation',
                'trainingProgram.items.controlledDocument',
            ])
            ->where('is_required', true)
            ->get();

        $users = User::query()
            ->with('designation')
            ->whereNotNull('designation_id')
            ->get()
            ->groupBy('designation_id');

        $completedDocumentIdsByUser = TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->whereNotNull('completed_at')
            ->get(['user_id', 'controlled_document_id'])
            ->groupBy('user_id')
            ->map(fn (Collection $rows): Collection => $rows->pluck('controlled_document_id')->map(fn (mixed $id): int => (int) $id));

        return $requirements->flatMap(function (RoleTrainingRequirement $requirement) use ($users, $completedDocumentIdsByUser): Collection {
            /** @var Designation|null $designation */
            $designation = $requirement->designation;
            /** @var TrainingProgram|null $program */
            $program = $requirement->trainingProgram;

            if ($designation === null || $program === null) {
                return collect();
            }

            $designationUsers = $users->get($designation->getKey(), collect());

            return $designationUsers->flatMap(function (User $user) use (
                $designation,
                $program,
                $completedDocumentIdsByUser,
            ): Collection {
                $completedIds = $completedDocumentIdsByUser->get($user->getKey(), collect());

                return $program->items
                    ->filter(fn (TrainingProgramItem $item): bool => (bool) $item->is_required)
                    ->map(function (TrainingProgramItem $item) use ($designation, $program, $user, $completedIds): array {
                        $document = $item->controlledDocument;
                        $documentId = (int) ($document?->getKey() ?? 0);
                        $isComplete = $completedIds->contains($documentId);

                        return [
                            'designation_code' => (string) $designation->code,
                            'designation_name' => (string) $designation->name,
                            'program_code' => (string) $program->code,
                            'program_name' => (string) $program->name,
                            'document_number' => (string) ($document?->document_number ?? '—'),
                            'document_title' => (string) ($document?->title ?? '—'),
                            'user_id' => (int) $user->getKey(),
                            'user_name' => (string) $user->name,
                            'assignment_status' => $isComplete ? 'Completed' : 'Pending',
                        ];
                    });
            });
        })->sortBy(fn (array $row): string => implode('|', [
            $row['designation_name'],
            $row['user_name'],
            $row['program_code'],
            $row['document_number'],
        ]))->values();
    }
}
