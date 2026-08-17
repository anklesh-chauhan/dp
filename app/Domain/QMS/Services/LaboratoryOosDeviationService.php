<?php

declare(strict_types=1);

namespace App\Domain\QMS\Services;

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Enums\ProductModule;
use App\Models\User;
use App\Support\Modules\ModuleManager;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LaboratoryOosDeviationService
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
    ) {}

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(
        LaboratoryOosEvent $event,
        User $actor,
        DeviationSeverity $severity,
        string $reason,
        ?string $immediateActions = null,
        ?CarbonInterface $investigationDueAt = null,
    ): Deviation {
        $this->moduleManager->ensureEnabled(ProductModule::QMS);

        if (! $actor->can('Investigate:LaboratoryOosEvent') || ! $actor->can('Create:Deviation')) {
            throw new AuthorizationException('You do not have permission to open a deviation from this laboratory OOS event.');
        }

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required to open a deviation from a laboratory OOS event.',
            ]);
        }

        return DB::transaction(function () use ($event, $actor, $severity, $reason, $immediateActions, $investigationDueAt): Deviation {
            $record = LaboratoryOosEvent::query()->lockForUpdate()->findOrFail($event->getKey());

            if ($record->deviation_id !== null) {
                return Deviation::query()->findOrFail($record->deviation_id);
            }

            if ($record->status !== LaboratoryOosStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'status' => 'Only confirmed laboratory OOS/OOT events can open a deviation.',
                ]);
            }

            $description = trim(implode("\n\n", array_filter([
                "Laboratory {$record->type->value} event {$record->event_number}.",
                "Test: {$record->test_name}.",
                filled($record->observed_result) ? "Observed result: {$record->observed_result}".(filled($record->unit) ? " {$record->unit}" : '').'.' : null,
                filled($record->specification_limit) ? "Specification limit: {$record->specification_limit}." : null,
                filled($record->phase_one_notes) ? "Phase I notes: {$record->phase_one_notes}" : null,
                filled($record->phase_two_notes) ? "Phase II notes: {$record->phase_two_notes}" : null,
                'Handoff reason: '.trim($reason),
            ])));

            $deviation = Deviation::query()->create([
                'title' => $record->title,
                'description' => $description,
                'immediate_actions' => filled($immediateActions) ? trim((string) $immediateActions) : null,
                'severity' => $severity,
                'occurred_at' => $record->started_at ?? now(),
                'discovered_at' => now(),
                'department_id' => $record->department_id,
                'reported_by' => $actor->getKey(),
                'owner_id' => $record->owner_id,
                'investigation_due_at' => $investigationDueAt ?? today()->addDays(30),
            ]);

            $record->update([
                'deviation_id' => $deviation->getKey(),
            ]);

            $record->auditEvents()->create([
                'event_uuid' => (string) Str::uuid(),
                'from_status' => $record->status,
                'to_status' => $record->status,
                'actor_id' => $actor->getKey(),
                'reason' => trim($reason),
                'context' => ['deviation_id' => $deviation->getKey()],
                'occurred_at' => now(),
            ]);

            return $deviation->refresh();
        });
    }
}
