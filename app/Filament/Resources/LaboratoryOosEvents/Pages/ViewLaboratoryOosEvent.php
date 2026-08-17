<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOosEvents\Pages;

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Services\LaboratoryOosDeviationService;
use App\Domain\QMS\Services\LaboratoryOosTransitionService;
use App\Filament\Resources\LaboratoryOosEvents\LaboratoryOosEventResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

final class ViewLaboratoryOosEvent extends ViewRecord
{
    protected static string $resource = LaboratoryOosEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => LaboratoryOosEventResource::canEdit($this->record)),
            $this->transitionAction(
                'beginPhaseOne',
                'Begin Phase I',
                LaboratoryOosStatus::PhaseOne,
                [LaboratoryOosStatus::Draft],
                'Investigate:LaboratoryOosEvent',
            ),
            $this->transitionAction(
                'beginPhaseTwo',
                'Begin Phase II',
                LaboratoryOosStatus::PhaseTwo,
                [LaboratoryOosStatus::PhaseOne, LaboratoryOosStatus::InvalidationProposed],
                'Investigate:LaboratoryOosEvent',
                requirePhaseOneOutcome: true,
            ),
            $this->transitionAction(
                'proposeInvalidation',
                'Propose Invalidation',
                LaboratoryOosStatus::InvalidationProposed,
                [LaboratoryOosStatus::PhaseOne, LaboratoryOosStatus::PhaseTwo],
                'Investigate:LaboratoryOosEvent',
                requirePhaseOneOutcome: true,
                requireInvalidation: true,
            ),
            $this->transitionAction(
                'confirm',
                'Confirm OOS/OOT',
                LaboratoryOosStatus::Confirmed,
                [LaboratoryOosStatus::PhaseOne, LaboratoryOosStatus::PhaseTwo],
                'Confirm:LaboratoryOosEvent',
                'success',
                requirePhaseOneOutcome: true,
            ),
            $this->transitionAction(
                'close',
                'Close',
                LaboratoryOosStatus::Closed,
                [LaboratoryOosStatus::Confirmed, LaboratoryOosStatus::InvalidationProposed],
                'Close:LaboratoryOosEvent',
                'success',
            ),
            $this->transitionAction(
                'cancel',
                'Cancel',
                LaboratoryOosStatus::Cancelled,
                [
                    LaboratoryOosStatus::Draft,
                    LaboratoryOosStatus::PhaseOne,
                    LaboratoryOosStatus::PhaseTwo,
                    LaboratoryOosStatus::InvalidationProposed,
                    LaboratoryOosStatus::Confirmed,
                ],
                'Manage:LaboratoryOosEvent',
                'danger',
            ),
            $this->openDeviationAction(),
        ];
    }

    private function openDeviationAction(): Action
    {
        return Action::make('openDeviation')
            ->label('Open Deviation')
            ->color('warning')
            ->schema([
                Select::make('severity')
                    ->options(DeviationSeverity::class)
                    ->required(),
                Textarea::make('immediate_actions')
                    ->rows(3),
                DatePicker::make('investigation_due_at'),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Handoff reason',
                    helperText: 'Explain why this confirmed OOS/OOT requires a deviation.',
                    context: fn (): array => [
                        'record_type' => 'Laboratory OOS deviation handoff',
                        'subject' => $this->record->event_number ?? (string) $this->record->getKey(),
                        'decision' => 'Open Deviation',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->status === LaboratoryOosStatus::Confirmed
                && $this->record->deviation_id === null
                && (bool) auth()->user()?->can('Investigate:LaboratoryOosEvent')
                && (bool) auth()->user()?->can('Create:Deviation'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                $severity = $data['severity'] instanceof DeviationSeverity
                    ? $data['severity']
                    : DeviationSeverity::from($data['severity']);

                $deviation = app(LaboratoryOosDeviationService::class)->create(
                    $this->record,
                    $user,
                    $severity,
                    $data['reason'],
                    filled($data['immediate_actions'] ?? null) ? $data['immediate_actions'] : null,
                    filled($data['investigation_due_at'] ?? null) ? Carbon::parse($data['investigation_due_at']) : null,
                );

                $this->record->refresh();
                $this->refreshFormData(['status', 'deviation_id']);

                Notification::make()
                    ->success()
                    ->title('Deviation opened')
                    ->body($deviation->deviation_number)
                    ->send();
            });
    }

    /** @param list<LaboratoryOosStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        LaboratoryOosStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
        bool $requirePhaseOneOutcome = false,
        bool $requireInvalidation = false,
    ): Action {
        $schema = [];

        if ($requirePhaseOneOutcome) {
            $schema[] = Select::make('phase_one_outcome')
                ->options(LaboratoryOosPhaseOutcome::class)
                ->required(fn (): bool => $this->record->phase_one_outcome === null
                    || $this->record->phase_one_outcome === LaboratoryOosPhaseOutcome::Pending)
                ->default(fn () => $this->record->phase_one_outcome?->value);

            $schema[] = Textarea::make('phase_one_notes')
                ->rows(3)
                ->default(fn () => $this->record->phase_one_notes);
        }

        if ($requireInvalidation) {
            $schema[] = Textarea::make('invalidation_justification')
                ->required(fn (): bool => blank($this->record->invalidation_justification))
                ->default(fn () => $this->record->invalidation_justification)
                ->rows(4);
        }

        $schema[] = ApprovalNarrativeTextarea::decisionRationale(
            name: 'reason',
            label: 'Decision reason',
            helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
            context: fn (): array => [
                'record_type' => 'Laboratory OOS lifecycle decision',
                'subject' => $this->record->event_number ?? (string) $this->record->getKey(),
                'decision' => $label,
            ],
        );

        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema($schema)
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label, $requirePhaseOneOutcome, $requireInvalidation): void {
                /** @var User $user */
                $user = auth()->user();

                $phaseOneOutcome = null;
                if ($requirePhaseOneOutcome && filled($data['phase_one_outcome'] ?? null)) {
                    $phaseOneOutcome = $data['phase_one_outcome'] instanceof LaboratoryOosPhaseOutcome
                        ? $data['phase_one_outcome']
                        : LaboratoryOosPhaseOutcome::from($data['phase_one_outcome']);
                }

                app(LaboratoryOosTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    phaseOneOutcome: $phaseOneOutcome,
                    phaseOneNotes: $requirePhaseOneOutcome ? ($data['phase_one_notes'] ?? null) : null,
                    invalidationJustification: $requireInvalidation ? ($data['invalidation_justification'] ?? null) : null,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'phase_one_outcome',
                    'phase_one_notes',
                    'invalidation_justification',
                    'started_at',
                    'phase_one_completed_at',
                    'phase_two_completed_at',
                    'closed_at',
                ]);

                Notification::make()->success()->title("Laboratory OOS: {$label}")->send();
            });
    }
}
