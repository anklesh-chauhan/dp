<?php

declare(strict_types=1);

namespace App\Filament\Resources\Complaints\Pages;

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Enums\ComplaintType;
use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\ProductRecallType;
use App\Domain\QMS\Models\ProductRecall;
use App\Domain\QMS\Services\ComplaintDeviationService;
use App\Domain\QMS\Services\ComplaintTransitionService;
use App\Domain\QMS\Services\ProductRecallFromComplaintService;
use App\Filament\Resources\Complaints\ComplaintResource;
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

final class ViewComplaint extends ViewRecord
{
    protected static string $resource = ComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => ComplaintResource::canEdit($this->record)),
            $this->transitionAction('markReceived', 'Mark Received', ComplaintStatus::Received, [ComplaintStatus::Draft], 'Assess:Complaint'),
            $this->transitionAction('beginAssessment', 'Begin Assessment', ComplaintStatus::UnderAssessment, [ComplaintStatus::Received], 'Assess:Complaint'),
            $this->transitionAction('beginInvestigation', 'Begin Investigation', ComplaintStatus::UnderInvestigation, [ComplaintStatus::UnderAssessment], 'Investigate:Complaint'),
            $this->transitionAction('awaitResponse', 'Await Response', ComplaintStatus::ResponsePending, [ComplaintStatus::UnderAssessment, ComplaintStatus::UnderInvestigation], 'Respond:Complaint'),
            $this->transitionAction('returnToInvestigation', 'Return to Investigation', ComplaintStatus::UnderInvestigation, [ComplaintStatus::ResponsePending], 'Investigate:Complaint'),
            $this->transitionAction('close', 'Close', ComplaintStatus::Closed, [ComplaintStatus::ResponsePending], 'Close:Complaint', 'success'),
            $this->transitionAction('reject', 'Reject', ComplaintStatus::Rejected, [ComplaintStatus::Received, ComplaintStatus::UnderAssessment, ComplaintStatus::UnderInvestigation], 'Assess:Complaint', 'danger'),
            $this->transitionAction('cancel', 'Cancel', ComplaintStatus::Cancelled, [
                ComplaintStatus::Draft,
                ComplaintStatus::Received,
                ComplaintStatus::UnderAssessment,
                ComplaintStatus::UnderInvestigation,
                ComplaintStatus::ResponsePending,
            ], 'Manage:Complaint', 'danger'),
            $this->openDeviationAction(),
            $this->openProductRecallAction(),
        ];
    }

    private function openProductRecallAction(): Action
    {
        return Action::make('openProductRecall')
            ->label('Open Product Recall')
            ->color('danger')
            ->schema([
                Select::make('type')
                    ->options(ProductRecallType::class)
                    ->required()
                    ->default(ProductRecallType::Market->value),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Handoff reason',
                    helperText: 'Explain why this complaint requires a market product recall.',
                    context: fn (): array => [
                        'record_type' => 'Complaint product recall handoff',
                        'subject' => $this->record->complaint_number ?? (string) $this->record->getKey(),
                        'decision' => 'Open Product Recall',
                    ],
                ),
            ])
            ->visible(fn (): bool => ! in_array($this->record->status, [
                ComplaintStatus::Closed,
                ComplaintStatus::Rejected,
                ComplaintStatus::Cancelled,
            ], true)
                && ProductRecall::query()->where('complaint_id', $this->record->getKey())->doesntExist()
                && (bool) auth()->user()?->can('View:Complaint')
                && (bool) auth()->user()?->can('Create:ProductRecall'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                $type = $data['type'] instanceof ProductRecallType
                    ? $data['type']
                    : ProductRecallType::from($data['type']);

                $recall = app(ProductRecallFromComplaintService::class)->create(
                    $this->record,
                    $user,
                    $data['reason'],
                    $type,
                );

                Notification::make()
                    ->success()
                    ->title('Product recall opened')
                    ->body($recall->recall_number)
                    ->send();
            });
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
                    helperText: 'Explain why this complaint requires a deviation investigation.',
                    context: fn (): array => [
                        'record_type' => 'Complaint deviation handoff',
                        'subject' => $this->record->complaint_number ?? (string) $this->record->getKey(),
                        'decision' => 'Open Deviation',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->type === ComplaintType::ProductQuality
                && in_array($this->record->status, [ComplaintStatus::UnderAssessment, ComplaintStatus::UnderInvestigation], true)
                && $this->record->deviations()->doesntExist()
                && (bool) auth()->user()?->can('Investigate:Complaint')
                && (bool) auth()->user()?->can('Create:Deviation'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                $severity = $data['severity'] instanceof DeviationSeverity
                    ? $data['severity']
                    : DeviationSeverity::from($data['severity']);

                $deviation = app(ComplaintDeviationService::class)->create(
                    $this->record,
                    $user,
                    $severity,
                    $data['reason'],
                    filled($data['immediate_actions'] ?? null) ? $data['immediate_actions'] : null,
                    filled($data['investigation_due_at'] ?? null) ? Carbon::parse($data['investigation_due_at']) : null,
                );

                $this->record->refresh();
                $this->refreshFormData(['status']);

                Notification::make()
                    ->success()
                    ->title('Deviation opened')
                    ->body($deviation->deviation_number)
                    ->send();
            });
    }

    /** @param list<ComplaintStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        ComplaintStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
    ): Action {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema([
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
                    context: fn (): array => [
                        'record_type' => 'Complaint lifecycle decision',
                        'subject' => $this->record->complaint_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(ComplaintTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'acknowledged_at', 'closed_at']);

                Notification::make()->success()->title("Complaint: {$label}")->send();
            });
    }
}
