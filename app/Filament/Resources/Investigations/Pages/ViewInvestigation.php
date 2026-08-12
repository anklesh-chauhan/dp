<?php

declare(strict_types=1);

namespace App\Filament\Resources\Investigations\Pages;

use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Services\InvestigationTransitionService;
use App\Filament\Resources\Investigations\InvestigationResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewInvestigation extends ViewRecord
{
    protected static string $resource = InvestigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => InvestigationResource::canEdit($this->record)),
            $this->transitionAction('begin', 'Begin Investigation', InvestigationStatus::InProgress, [InvestigationStatus::Draft], 'Update:Investigation'),
            $this->transitionAction('submitReview', 'Submit for Review', InvestigationStatus::PendingReview, [InvestigationStatus::InProgress], 'Review:Investigation'),
            $this->transitionAction('returnToInvestigation', 'Return to Investigation', InvestigationStatus::InProgress, [InvestigationStatus::PendingReview], 'Update:Investigation'),
            $this->transitionAction('complete', 'Complete', InvestigationStatus::Completed, [InvestigationStatus::PendingReview], 'Complete:Investigation', 'success'),
            $this->transitionAction('cancel', 'Cancel', InvestigationStatus::Cancelled, [InvestigationStatus::Draft, InvestigationStatus::InProgress, InvestigationStatus::PendingReview], 'Manage:Investigation', 'danger'),
        ];
    }

    /** @param list<InvestigationStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        InvestigationStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
    ): Action {
        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema([
                $name === 'submitReview'
                    ? ApprovalNarrativeTextarea::submissionNote(
                        context: fn (): array => [
                            'record_type' => 'Investigation review submission',
                            'subject' => $this->record->investigation_number ?? (string) $this->record->getKey(),
                            'decision' => $label,
                        ],
                    )
                    : ApprovalNarrativeTextarea::decisionRationale(
                        name: 'reason',
                        label: 'Decision reason',
                        helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
                        context: fn (): array => [
                            'record_type' => 'Investigation lifecycle decision',
                            'subject' => $this->record->investigation_number ?? (string) $this->record->getKey(),
                            'decision' => $label,
                        ],
                    ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(InvestigationTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'started_at', 'completed_at']);

                Notification::make()->success()->title("Investigation: {$label}")->send();
            });
    }
}
