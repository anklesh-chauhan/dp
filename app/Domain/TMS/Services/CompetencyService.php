<?php

declare(strict_types=1);

namespace App\Domain\TMS\Services;

use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Enums\UserCompetencyStatus;
use App\Domain\TMS\Models\CompetencyCurriculum;
use App\Domain\TMS\Models\CompetencyCurriculumItem;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Domain\TMS\Models\UserCompetency;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompetencyService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
    ) {}

    public function assignCurriculum(
        User $user,
        CompetencyCurriculum $curriculum,
        User $actor,
    ): UserCompetency {
        $this->ensureCompetencyModuleEnabled();

        if (! $actor->can('Assign:UserCompetency') && ! $actor->can('Assign:CompetencyCurriculum')) {
            throw new AuthorizationException('You do not have permission to assign competency curricula.');
        }

        $existing = UserCompetency::query()
            ->where('user_id', $user->getKey())
            ->where('curriculum_id', $curriculum->getKey())
            ->first();

        if ($existing instanceof UserCompetency) {
            throw ValidationException::withMessages([
                'curriculum_id' => 'This user is already assigned to the selected curriculum.',
            ]);
        }

        return DB::transaction(function () use ($user, $curriculum, $actor): UserCompetency {
            $competency = UserCompetency::query()->create([
                'user_id' => $user->getKey(),
                'curriculum_id' => $curriculum->getKey(),
                'status' => UserCompetencyStatus::Assigned,
                'assigned_by' => $actor->getKey(),
                'assigned_at' => now(),
            ]);

            return $this->refreshUserCompetency($competency->fresh(['curriculum.items']) ?? $competency);
        });
    }

    public function refreshUserCompetency(UserCompetency $competency): UserCompetency
    {
        $competency->loadMissing('curriculum.items');

        $curriculum = $competency->curriculum;

        if (! $curriculum instanceof CompetencyCurriculum) {
            throw ValidationException::withMessages([
                'curriculum' => 'Competency record is not linked to a curriculum.',
            ]);
        }

        $requiredDocumentIds = $curriculum->items
            ->filter(fn (CompetencyCurriculumItem $item): bool => $item->is_required)
            ->pluck('controlled_document_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $allRequiredTrained = $requiredDocumentIds->isEmpty()
            || $this->completedDocumentIdsFor($competency->user_id, $requiredDocumentIds->all())
                ->count() === $requiredDocumentIds->count();

        if (! $allRequiredTrained) {
            $competency->update([
                'status' => UserCompetencyStatus::Assigned,
                'trained_at' => null,
                'expires_at' => null,
            ]);

            return $competency->refresh();
        }

        $trainedAt = $competency->trained_at ?? now();
        $expiresAt = $this->resolveExpiresAt($curriculum, $trainedAt);

        if ($expiresAt !== null && $expiresAt->isPast()) {
            $competency->update([
                'status' => UserCompetencyStatus::Expired,
                'trained_at' => $trainedAt,
                'expires_at' => $expiresAt,
            ]);

            return $competency->refresh();
        }

        $competency->update([
            'status' => UserCompetencyStatus::Trained,
            'trained_at' => $trainedAt,
            'expires_at' => $expiresAt,
        ]);

        return $competency->refresh();
    }

    public function refreshForUserDocument(User $user, int $controlledDocumentId): void
    {
        $competencies = UserCompetency::query()
            ->where('user_id', $user->getKey())
            ->whereHas('curriculum.items', function ($query) use ($controlledDocumentId): void {
                $query->where('controlled_document_id', $controlledDocumentId);
            })
            ->with('curriculum.items')
            ->get();

        foreach ($competencies as $competency) {
            $this->refreshUserCompetency($competency);
        }
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function assertCompetent(User $user, CompetencyCurriculum|string $curriculum): void
    {
        $record = $curriculum instanceof CompetencyCurriculum
            ? $curriculum
            : CompetencyCurriculum::query()->where('code', $curriculum)->first();

        if (! $record instanceof CompetencyCurriculum) {
            throw ValidationException::withMessages([
                'curriculum' => 'Competency curriculum not found.',
            ]);
        }

        $competency = UserCompetency::query()
            ->where('user_id', $user->getKey())
            ->where('curriculum_id', $record->getKey())
            ->first();

        if (! $competency instanceof UserCompetency) {
            throw new AuthorizationException(
                "User is not assigned to competency curriculum [{$record->code}].",
            );
        }

        $competency = $this->refreshUserCompetency($competency);

        if (! $competency->isCurrent()) {
            $status = $competency->status->getLabel();

            throw new AuthorizationException(
                "User competency for [{$record->code}] is not current (status: {$status}).",
            );
        }
    }

    public function verify(UserCompetency $competency, User $verifier): UserCompetency
    {
        $competency = $this->refreshUserCompetency($competency);

        if (! $competency->isCurrent()) {
            throw ValidationException::withMessages([
                'competency' => 'Only current (trained) competencies can be verified.',
            ]);
        }

        $competency->update([
            'verified_by' => $verifier->getKey(),
            'verified_at' => now(),
        ]);

        return $competency->refresh();
    }

    /**
     * @return Collection<int, array{role: string, curriculum_code: string, curriculum_name: string, user_id: int, user_name: string, status: string, trained_at: ?string, expires_at: ?string}>
     */
    public function competencyByRole(): Collection
    {
        return UserCompetency::query()
            ->with(['user', 'curriculum.sopRole'])
            ->get()
            ->sortBy(fn (UserCompetency $row): string => $row->curriculum?->roleLabel().'|'.$row->user?->name)
            ->values()
            ->map(fn (UserCompetency $row): array => [
                'role' => $row->curriculum?->roleLabel() ?? 'Unassigned role',
                'curriculum_code' => (string) $row->curriculum?->code,
                'curriculum_name' => (string) $row->curriculum?->name,
                'user_id' => (int) $row->user_id,
                'user_name' => (string) ($row->user?->name ?? ''),
                'status' => $row->status->value,
                'trained_at' => $row->trained_at?->toIso8601String(),
                'expires_at' => $row->expires_at?->toIso8601String(),
            ]);
    }

    /**
     * @param  list<int>  $documentIds
     * @return Collection<int, int>
     */
    private function completedDocumentIdsFor(int $userId, array $documentIds): Collection
    {
        if ($documentIds === []) {
            return collect();
        }

        return TrainingAssignment::query()
            ->where('source_type', TrainingAssignmentSource::ControlledDocument)
            ->where('user_id', $userId)
            ->whereIn('controlled_document_id', $documentIds)
            ->whereNotNull('completed_at')
            ->pluck('controlled_document_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    private function resolveExpiresAt(
        CompetencyCurriculum $curriculum,
        mixed $trainedAt,
    ): ?Carbon {
        $months = $curriculum->requalification_months;

        if ($months === null || $months <= 0) {
            return null;
        }

        return Carbon::parse($trainedAt)->addMonths($months);
    }

    private function ensureCompetencyModuleEnabled(): void
    {
        if ($this->moduleManager->enabled(ProductModule::TMS)) {
            return;
        }

        $this->moduleManager->ensureEnabled(ProductModule::QMS);
    }
}
