<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations\Pages;

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Services\DeviationApprovalSubmissionService;
use App\Domain\QMS\Services\DeviationTransitionService;
use App\Filament\Resources\Deviations\DeviationResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewDeviation extends ViewRecord
{
    protected static string $resource = DeviationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => DeviationResource::canEdit($this->record)),
            $this->submissionAction(),
            $this->transitionAction('beginInvestigation', 'Begin Investigation', DeviationStatus::UnderInvestigation, [DeviationStatus::Open], 'Investigate:Deviation'),
            $this->transitionAction('completeInvestigation', 'Complete Investigation', DeviationStatus::InvestigationComplete, [DeviationStatus::UnderInvestigation], 'Investigate:Deviation'),
            $this->transitionAction('requireCapa', 'Require CAPA', DeviationStatus::CapaRequired, [DeviationStatus::InvestigationComplete], 'Investigate:Deviation'),
            $this->transitionAction('beginEffectivenessReview', 'Begin Effectiveness Review', DeviationStatus::EffectivenessReview, [DeviationStatus::InvestigationComplete, DeviationStatus::CapaRequired], 'VerifyEffectiveness:Deviation'),
            $this->transitionAction('close', 'Close', DeviationStatus::Closed, [DeviationStatus::EffectivenessReview], 'Close:Deviation', 'success'),
            $this->transitionAction('reject', 'Reject', DeviationStatus::Rejected, [DeviationStatus::Open], 'Investigate:Deviation', 'danger'),
            $this->transitionAction('cancel', 'Cancel', DeviationStatus::Cancelled, [DeviationStatus::Draft, DeviationStatus::Open, DeviationStatus::UnderInvestigation], 'Manage:Deviation', 'danger'),
        ];
    }

    private function submissionAction(): Action
    {
        return Action::make('submit')
            ->label('Submit')
            ->schema([
                ApprovalNarrativeTextarea::submissionNote(
                    context: fn (): array => [
                        'record_type' => 'Deviation approval submission',
                        'subject' => $this->record->deviation_number ?? (string) $this->record->getKey(),
                        'department' => $this->record->department?->name,
                        'decision' => 'Submit',
                        'extra' => filled($this->record->title)
                            ? 'Title: '.$this->record->title
                            : null,
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->status === DeviationStatus::Draft
                && (bool) auth()->user()?->can('Submit:Deviation'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(DeviationApprovalSubmissionService::class)->submit(
                    $this->record,
                    $user,
                    $data['reason'],
                    request()->ip(),
                    request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status']);

                Notification::make()->success()->title('Deviation submitted')->send();
            });
    }

    /** @param list<DeviationStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        DeviationStatus $toStatus,
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
                        'record_type' => 'Deviation lifecycle decision',
                        'subject' => $this->record->deviation_number ?? (string) $this->record->getKey(),
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

                app(DeviationTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'closed_at']);

                Notification::make()->success()->title("Deviation: {$label}")->send();
            });
    }
}
