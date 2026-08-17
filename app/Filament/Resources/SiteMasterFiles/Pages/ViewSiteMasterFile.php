<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles\Pages;

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Services\SiteMasterFileTransitionService;
use App\Filament\Resources\SiteMasterFiles\SiteMasterFileResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewSiteMasterFile extends ViewRecord
{
    protected static string $resource = SiteMasterFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => SiteMasterFileResource::canEdit($this->record)),
            $this->transitionAction(
                'submitReview',
                'Submit for Review',
                SiteMasterFileStatus::InReview,
                [SiteMasterFileStatus::Draft],
                'Update:SiteMasterFile',
            ),
            $this->transitionAction(
                'returnToDraft',
                'Return to Draft',
                SiteMasterFileStatus::Draft,
                [SiteMasterFileStatus::InReview],
                'Update:SiteMasterFile',
            ),
            $this->transitionAction(
                'publish',
                'Publish',
                SiteMasterFileStatus::Published,
                [SiteMasterFileStatus::InReview],
                'Publish:SiteMasterFile',
                'success',
            ),
            $this->transitionAction(
                'retire',
                'Retire',
                SiteMasterFileStatus::Retired,
                [SiteMasterFileStatus::Published],
                'Retire:SiteMasterFile',
                'danger',
            ),
        ];
    }

    /** @param list<SiteMasterFileStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        SiteMasterFileStatus $toStatus,
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
                        'record_type' => 'Site Master File lifecycle decision',
                        'subject' => $this->record->smf_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(SiteMasterFileTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'published_by', 'published_at', 'version', 'sections']);

                Notification::make()->success()->title("Site Master File: {$label}")->send();
            });
    }
}
