<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments\Pages;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Services\InspectorEvidencePackService;
use App\Domain\QMS\Services\ScheduleMGapAssessmentTransitionService;
use App\Filament\Resources\ScheduleMGapAssessments\ScheduleMGapAssessmentResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ViewScheduleMGapAssessment extends ViewRecord
{
    protected static string $resource = ScheduleMGapAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => ScheduleMGapAssessmentResource::canEdit($this->record)),
            $this->downloadInspectorPackAction(),
            $this->transitionAction(
                'begin',
                'Begin Assessment',
                ScheduleMGapAssessmentStatus::InProgress,
                [ScheduleMGapAssessmentStatus::Draft],
                'Conduct:ScheduleMGapAssessment',
            ),
            $this->transitionAction(
                'submitReview',
                'Submit for Review',
                ScheduleMGapAssessmentStatus::UnderReview,
                [ScheduleMGapAssessmentStatus::InProgress],
                'Conduct:ScheduleMGapAssessment',
            ),
            $this->transitionAction(
                'returnToProgress',
                'Return to In Progress',
                ScheduleMGapAssessmentStatus::InProgress,
                [ScheduleMGapAssessmentStatus::UnderReview],
                'Conduct:ScheduleMGapAssessment',
            ),
            $this->transitionAction(
                'approve',
                'Approve',
                ScheduleMGapAssessmentStatus::Approved,
                [ScheduleMGapAssessmentStatus::UnderReview],
                'Approve:ScheduleMGapAssessment',
                'success',
            ),
            $this->transitionAction(
                'close',
                'Close',
                ScheduleMGapAssessmentStatus::Closed,
                [ScheduleMGapAssessmentStatus::Approved],
                'Close:ScheduleMGapAssessment',
                'success',
            ),
            $this->transitionAction(
                'cancel',
                'Cancel',
                ScheduleMGapAssessmentStatus::Cancelled,
                [
                    ScheduleMGapAssessmentStatus::Draft,
                    ScheduleMGapAssessmentStatus::InProgress,
                    ScheduleMGapAssessmentStatus::UnderReview,
                ],
                'Manage:ScheduleMGapAssessment',
                'danger',
            ),
        ];
    }

    private function downloadInspectorPackAction(): Action
    {
        return Action::make('downloadInspectorPack')
            ->label('Download Inspector Pack')
            ->icon(Heroicon::ArrowDownTray)
            ->visible(fn (): bool => (bool) auth()->user()?->can('Export:InspectorEvidencePack')
                || (bool) auth()->user()?->can('View:ScheduleMGapAssessment'))
            ->action(function (): StreamedResponse {
                /** @var User $user */
                $user = auth()->user();
                /** @var ScheduleMGapAssessment $record */
                $record = $this->record;

                $pack = app(InspectorEvidencePackService::class)->build($record, $user);
                $filename = sprintf(
                    'inspector-evidence-pack-%s.json',
                    $record->assessment_number ?? $record->getKey(),
                );

                return response()->streamDownload(
                    function () use ($pack): void {
                        echo json_encode($pack, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    },
                    $filename,
                    ['Content-Type' => 'application/json'],
                );
            });
    }

    /** @param list<ScheduleMGapAssessmentStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        ScheduleMGapAssessmentStatus $toStatus,
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
                        'record_type' => 'Schedule M Gap Assessment lifecycle decision',
                        'subject' => $this->record->assessment_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(ScheduleMGapAssessmentTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'approved_at', 'closed_at']);

                Notification::make()->success()->title("Schedule M Gap Assessment: {$label}")->send();
            });
    }
}
