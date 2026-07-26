<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\AuditFindingClassification;
use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Enums\CapaType;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\Capa;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AuditFindingCapaService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly AuditFindingTransitionService $auditFindingTransitionService,
    ) {}

    public function create(
        AuditFinding $finding,
        User $actor,
        User $owner,
        CapaType $type,
        string $actionPlan,
        string $reason,
        CarbonInterface $dueAt,
        ?CarbonInterface $effectivenessDueAt = null,
    ): Capa {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Respond:AuditFinding') || ! $actor->can('Create:Capa')) {
            throw new AuthorizationException('You do not have permission to open a CAPA from this audit finding.');
        }

        $normalizedPlan = trim($actionPlan);
        $normalizedReason = trim($reason);
        if ($normalizedPlan === '' || $normalizedReason === '') {
            throw ValidationException::withMessages([
                'action_plan' => 'An action plan and attributable reason are required.',
            ]);
        }

        if ($effectivenessDueAt !== null && $effectivenessDueAt->lt($dueAt)) {
            throw ValidationException::withMessages([
                'effectiveness_due_at' => 'Effectiveness verification cannot be due before CAPA implementation.',
            ]);
        }

        return DB::transaction(function () use (
            $finding,
            $actor,
            $owner,
            $type,
            $normalizedPlan,
            $normalizedReason,
            $dueAt,
            $effectivenessDueAt,
        ): Capa {
            $record = AuditFinding::query()->lockForUpdate()->findOrFail($finding->getKey());
            $existing = $record->capas()->first();

            if ($existing instanceof Capa) {
                return $existing;
            }

            if ($record->classification !== AuditFindingClassification::Nonconformity) {
                throw ValidationException::withMessages([
                    'classification' => 'Only a nonconformity can open a CAPA.',
                ]);
            }

            if (! in_array($record->disposition, [
                AuditFindingDisposition::Open,
                AuditFindingDisposition::ResponsePending,
            ], true)) {
                throw ValidationException::withMessages([
                    'disposition' => 'The finding must be open or awaiting response before opening a CAPA.',
                ]);
            }

            $capa = $record->capas()->create([
                'deviation_id' => null,
                'investigation_id' => null,
                'type' => $type,
                'title' => $record->title,
                'action_plan' => $normalizedPlan,
                'owner_id' => $owner->getKey(),
                'due_at' => $dueAt,
                'effectiveness_due_at' => $effectivenessDueAt,
            ]);

            if ($record->disposition === AuditFindingDisposition::Open) {
                $this->auditFindingTransitionService->transition(
                    $record,
                    AuditFindingDisposition::ResponsePending,
                    $actor,
                    $normalizedReason,
                    context: ['capa_id' => $capa->getKey()],
                );
            }

            return $capa->refresh();
        });
    }
}
