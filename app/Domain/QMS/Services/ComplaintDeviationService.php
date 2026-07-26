<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\Deviation;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ComplaintDeviationService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly ComplaintTransitionService $complaintTransitionService,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(
        Complaint $complaint,
        User $actor,
        DeviationSeverity $severity,
        string $reason,
        ?string $immediateActions = null,
        ?CarbonInterface $investigationDueAt = null,
    ): Deviation {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Investigate:Complaint') || ! $actor->can('Create:Deviation')) {
            throw new AuthorizationException('You do not have permission to open a deviation from this complaint.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required to open a deviation from a complaint.',
            ]);
        }

        return DB::transaction(function () use ($complaint, $actor, $severity, $reason, $immediateActions, $investigationDueAt): Deviation {
            $record = Complaint::query()->lockForUpdate()->findOrFail($complaint->getKey());
            $existing = $record->deviations()->first();

            if ($existing instanceof Deviation) {
                return $existing;
            }

            if ($record->type !== ComplaintType::ProductQuality) {
                throw ValidationException::withMessages([
                    'type' => 'Only product-quality complaints can open a deviation.',
                ]);
            }

            if (! in_array($record->status, [ComplaintStatus::UnderAssessment, ComplaintStatus::UnderInvestigation], true)) {
                throw ValidationException::withMessages([
                    'status' => 'The complaint must be under assessment or investigation before opening a deviation.',
                ]);
            }

            $deviation = $record->deviations()->create([
                'title' => $record->title,
                'description' => $record->description,
                'immediate_actions' => filled($immediateActions) ? trim((string) $immediateActions) : null,
                'severity' => $severity,
                'occurred_at' => $record->received_at,
                'discovered_at' => now(),
                'department_id' => $record->department_id,
                'reported_by' => $actor->getKey(),
                'owner_id' => $record->owner_id,
                'investigation_due_at' => $investigationDueAt ?? today()->addDays(30),
            ]);

            if ($record->status === ComplaintStatus::UnderAssessment) {
                $this->complaintTransitionService->transition(
                    $record,
                    ComplaintStatus::UnderInvestigation,
                    $actor,
                    trim($reason),
                    ['deviation_id' => $deviation->getKey()],
                );
            }

            return $deviation->refresh();
        });
    }
}
