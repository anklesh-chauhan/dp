<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\Pages;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Services\ChangeControlTransitionService;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Filament\Resources\ChangeControls\ChangeControlResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\ReportTemplate;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewChangeControl extends ViewRecord
{
    protected static string $resource = ChangeControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('report')
                ->label('Print Investigation')
                ->schema([
                    Select::make('template')
                        ->label('Report Template')
                        ->options(fn (): array => ReportTemplate::query()
                            ->active()
                            ->where('scope', ReportScope::ChangeControl)
                            ->where('format', ReportFormat::Pdf)
                            ->pluck('name', 'id')
                            ->all())
                        ->required(),
                ])
                ->action(fn (array $data): mixed => $this->redirect(route('change-controls.report', [
                    'changeControl' => $this->record,
                    'template' => $data['template'],
                ]))),
            EditAction::make()
                ->visible(fn (): bool => ChangeControlResource::canEdit($this->record)),
            Action::make('submit')
                ->label('Submit for Review')
                ->modalHeading('Submit this change control for review?')
                ->modalSubmitActionLabel('Submit for review')
                ->schema([
                    ApprovalNarrativeTextarea::submissionNote(
                        context: fn (): array => [
                            'record_type' => 'Change control approval submission',
                            'subject' => $this->record->change_number ?? (string) $this->record->getKey(),
                            'department' => $this->record->department?->name,
                            'decision' => 'Submit for Review',
                            'extra' => filled($this->record->title)
                                ? 'Title: '.$this->record->title
                                : null,
                        ],
                    ),
                ])
                ->visible(fn (): bool => $this->record->status === ChangeControlStatus::Draft
                    && (bool) auth()->user()?->can('Submit:ChangeControl'))
                ->action(function (array $data): void {
                    /** @var User $user */
                    $user = auth()->user();

                    app(ChangeControlTransitionService::class)->transition(
                        $this->record,
                        ChangeControlStatus::Submitted,
                        $user,
                        $data['reason'],
                        ipAddress: request()->ip(),
                        userAgent: request()->userAgent(),
                    );
                    $this->record->refresh();
                    $this->refreshFormData(['status', 'submitted_at']);

                    Notification::make()
                        ->success()
                        ->title('Change control submitted')
                        ->send();
                }),
            $this->transitionAction(
                'beginReview',
                'Begin Review',
                ChangeControlStatus::UnderReview,
                [ChangeControlStatus::Submitted],
                'Review:ChangeControl',
            ),
            $this->transitionAction(
                'approve',
                'Approve',
                ChangeControlStatus::Approved,
                [ChangeControlStatus::UnderReview],
                'Approve:ChangeControl',
                'success',
            ),
            $this->transitionAction(
                'reject',
                'Reject',
                ChangeControlStatus::Rejected,
                [ChangeControlStatus::Submitted, ChangeControlStatus::UnderReview],
                'Review:ChangeControl',
                'danger',
            ),
            $this->transitionAction(
                'cancel',
                'Cancel',
                ChangeControlStatus::Cancelled,
                [
                    ChangeControlStatus::Draft,
                    ChangeControlStatus::Submitted,
                    ChangeControlStatus::UnderReview,
                    ChangeControlStatus::Approved,
                ],
                'Manage:ChangeControl',
                'danger',
            ),
            $this->transitionAction(
                'beginEffectivenessReview',
                'Begin Effectiveness Review',
                ChangeControlStatus::EffectivenessReview,
                [ChangeControlStatus::Implementing],
                'VerifyEffectiveness:ChangeControl',
            ),
            $this->transitionAction(
                'close',
                'Close',
                ChangeControlStatus::Closed,
                [ChangeControlStatus::EffectivenessReview],
                'Close:ChangeControl',
                'success',
            ),
        ];
    }

    /**
     * @param  list<ChangeControlStatus>  $fromStatuses
     */
    private function transitionAction(
        string $name,
        string $label,
        ChangeControlStatus $toStatus,
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
                        'record_type' => 'Change control lifecycle decision',
                        'subject' => $this->record->change_number ?? (string) $this->record->getKey(),
                        'department' => $this->record->department?->name,
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(ChangeControlTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'submitted_at',
                    'approved_at',
                    'implemented_at',
                    'effectiveness_verified_at',
                    'closed_at',
                ]);

                Notification::make()
                    ->success()
                    ->title("Change control: {$label}")
                    ->send();
            });
    }
}
