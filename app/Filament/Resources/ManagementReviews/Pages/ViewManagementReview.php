<?php

declare(strict_types=1);

namespace App\Filament\Resources\ManagementReviews\Pages;

use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Services\ManagementReviewInputAssembler;
use App\Domain\QMS\Services\ManagementReviewPackService;
use App\Domain\QMS\Services\ManagementReviewTransitionService;
use App\Filament\Resources\ManagementReviews\ManagementReviewResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ViewManagementReview extends ViewRecord
{
    protected static string $resource = ManagementReviewResource::class;

    public string $assembledInputPreview = '';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => ManagementReviewResource::canEdit($this->record)),
            $this->assembleSuggestedInputsAction(),
            $this->downloadPackAction(),
            $this->transitionAction('schedule', 'Schedule', ManagementReviewStatus::Scheduled, [ManagementReviewStatus::Draft], 'Schedule:ManagementReview'),
            $this->transitionAction('begin', 'Begin Review', ManagementReviewStatus::InProgress, [ManagementReviewStatus::Scheduled], 'Conduct:ManagementReview'),
            $this->transitionAction(
                'prepareMinutes',
                'Prepare Minutes',
                ManagementReviewStatus::MinutesPending,
                [ManagementReviewStatus::InProgress],
                'IssueMinutes:ManagementReview',
                requireInputsAndDecisions: true,
            ),
            $this->transitionAction(
                'issueMinutes',
                'Issue Minutes',
                ManagementReviewStatus::ActionsPending,
                [ManagementReviewStatus::MinutesPending],
                'IssueMinutes:ManagementReview',
                requireActionSummary: true,
            ),
            $this->transitionAction(
                'returnToMinutes',
                'Return to Minutes',
                ManagementReviewStatus::MinutesPending,
                [ManagementReviewStatus::ActionsPending],
                'IssueMinutes:ManagementReview',
            ),
            $this->transitionAction('complete', 'Complete', ManagementReviewStatus::Completed, [ManagementReviewStatus::ActionsPending], 'Complete:ManagementReview', 'success'),
            $this->transitionAction('cancel', 'Cancel', ManagementReviewStatus::Cancelled, [
                ManagementReviewStatus::Draft,
                ManagementReviewStatus::Scheduled,
                ManagementReviewStatus::InProgress,
                ManagementReviewStatus::MinutesPending,
            ], 'Manage:ManagementReview', 'danger'),
        ];
    }

    private function assembleSuggestedInputsAction(): Action
    {
        return Action::make('assembleSuggestedInputs')
            ->label('Assemble suggested inputs')
            ->icon(Heroicon::Sparkles)
            ->visible(fn (): bool => (bool) auth()->user()?->can('View:QualityMetrics'))
            ->fillForm(function (): array {
                /** @var User $user */
                $user = auth()->user();
                /** @var ManagementReview $record */
                $record = $this->record;

                $assembler = app(ManagementReviewInputAssembler::class);
                $markdown = $assembler->toMarkdown($assembler->assemble($record, $user));
                $this->assembledInputPreview = $markdown;

                return [
                    'preview' => $markdown,
                    'apply_to_input_summary' => false,
                ];
            })
            ->schema([
                Textarea::make('preview')
                    ->label('Suggested inputs preview')
                    ->rows(16)
                    ->readOnly()
                    ->dehydrated(),
                Toggle::make('apply_to_input_summary')
                    ->label('Apply preview to input summary')
                    ->helperText('Only available while the review is Draft or Scheduled.')
                    ->visible(fn (): bool => in_array($this->record->status, [
                        ManagementReviewStatus::Draft,
                        ManagementReviewStatus::Scheduled,
                    ], true)
                        && (bool) auth()->user()?->can('Update:ManagementReview')),
            ])
            ->action(function (array $data): void {
                $preview = (string) ($data['preview'] ?? $this->assembledInputPreview);
                $this->assembledInputPreview = $preview;

                $shouldApply = (bool) ($data['apply_to_input_summary'] ?? false)
                    && in_array($this->record->status, [
                        ManagementReviewStatus::Draft,
                        ManagementReviewStatus::Scheduled,
                    ], true)
                    && (bool) auth()->user()?->can('Update:ManagementReview');

                if ($shouldApply) {
                    $this->record->update(['input_summary' => $preview]);
                    $this->record->refresh();
                    $this->refreshFormData(['input_summary']);

                    Notification::make()
                        ->success()
                        ->title('Suggested inputs applied')
                        ->body('input_summary was updated from the assembled package.')
                        ->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Suggested inputs assembled')
                    ->body('Preview is ready. Apply is available for Draft or Scheduled reviews.')
                    ->send();
            });
    }

    private function downloadPackAction(): Action
    {
        return Action::make('downloadPack')
            ->label('Download pack as JSON')
            ->icon(Heroicon::ArrowDownTray)
            ->visible(fn (): bool => (bool) auth()->user()?->can('View:QualityMetrics'))
            ->action(function (): StreamedResponse {
                /** @var User $user */
                $user = auth()->user();
                /** @var ManagementReview $record */
                $record = $this->record;

                $pack = app(ManagementReviewPackService::class)->build($record, $user);
                $filename = sprintf(
                    'management-review-pack-%s.json',
                    $record->review_number ?? $record->getKey(),
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

    /** @param list<ManagementReviewStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        ManagementReviewStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
        bool $requireInputsAndDecisions = false,
        bool $requireActionSummary = false,
    ): Action {
        $schema = [];

        if ($requireInputsAndDecisions) {
            $schema[] = Textarea::make('input_summary')
                ->required(fn (): bool => blank($this->record->input_summary))
                ->default(fn () => $this->record->input_summary)
                ->rows(4);
            $schema[] = Textarea::make('decisions')
                ->required(fn (): bool => blank($this->record->decisions))
                ->default(fn () => $this->record->decisions)
                ->rows(4);
        }

        if ($requireActionSummary) {
            $schema[] = Textarea::make('action_summary')
                ->required(fn (): bool => blank($this->record->action_summary))
                ->default(fn () => $this->record->action_summary)
                ->rows(4);
        }

        $schema[] = ApprovalNarrativeTextarea::decisionRationale(
            name: 'reason',
            label: 'Decision reason',
            helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
            context: fn (): array => [
                'record_type' => 'Management Review lifecycle decision',
                'subject' => $this->record->review_number ?? (string) $this->record->getKey(),
                'decision' => $label,
            ],
        );

        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema($schema)
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission)
                && ($toStatus !== ManagementReviewStatus::Completed || (bool) auth()->user()?->can('Approve:ManagementReview')))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(ManagementReviewTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    inputSummary: $data['input_summary'] ?? null,
                    decisions: $data['decisions'] ?? null,
                    actionSummary: $data['action_summary'] ?? null,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'input_summary',
                    'decisions',
                    'action_summary',
                    'held_at',
                    'minutes_issued_at',
                    'approved_at',
                    'completed_at',
                ]);

                Notification::make()->success()->title("Management Review: {$label}")->send();
            });
    }
}
