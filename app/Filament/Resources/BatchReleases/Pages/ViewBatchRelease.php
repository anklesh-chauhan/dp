<?php

declare(strict_types=1);

namespace App\Filament\Resources\BatchReleases\Pages;

use App\Domain\QMS\Enums\BatchReleaseStatus;
use App\Domain\QMS\Services\BatchReleaseTransitionService;
use App\Filament\Resources\BatchReleases\BatchReleaseResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewBatchRelease extends ViewRecord
{
    protected static string $resource = BatchReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->transitionAction('submitReview', 'Submit for independent release', BatchReleaseStatus::UnderReview, [BatchReleaseStatus::Draft], 'Review:BatchRelease'),
            $this->transitionAction('release', 'Certify / release batch', BatchReleaseStatus::Released, [BatchReleaseStatus::UnderReview], 'Release:BatchRelease', 'success'),
            $this->transitionAction('reject', 'Reject batch', BatchReleaseStatus::Rejected, [BatchReleaseStatus::UnderReview], 'Release:BatchRelease', 'danger'),
            $this->transitionAction('cancel', 'Cancel', BatchReleaseStatus::Cancelled, [BatchReleaseStatus::Draft, BatchReleaseStatus::UnderReview], 'Manage:BatchRelease', 'danger'),
        ];
    }

    /** @param  list<BatchReleaseStatus>  $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        BatchReleaseStatus $toStatus,
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
                    context: fn (): array => [
                        'record_type' => 'Batch release',
                        'subject' => $this->record->release_number,
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true) && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();
                app(BatchReleaseTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                Notification::make()->success()->title("Batch release: {$label}")->send();
            });
    }
}
