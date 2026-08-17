<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalAudits\Pages;

use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Services\InternalAuditTransitionService;
use App\Filament\Resources\InternalAudits\InternalAuditResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewInternalAudit extends ViewRecord
{
    protected static string $resource = InternalAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => InternalAuditResource::canEdit($this->record)),
            $this->transitionAction('schedule', 'Schedule', InternalAuditStatus::Scheduled, [InternalAuditStatus::Draft], 'Schedule:InternalAudit'),
            $this->transitionAction('begin', 'Begin Audit', InternalAuditStatus::InProgress, [InternalAuditStatus::Scheduled], 'Conduct:InternalAudit'),
            $this->transitionAction('beginReporting', 'Begin Reporting', InternalAuditStatus::Reporting, [InternalAuditStatus::InProgress], 'Report:InternalAudit'),
            $this->transitionAction(
                'beginFollowUp',
                'Begin Follow-up',
                InternalAuditStatus::FollowUp,
                [InternalAuditStatus::Reporting],
                'FollowUp:InternalAudit',
                milestoneFields: true,
            ),
            $this->transitionAction(
                'close',
                'Close',
                InternalAuditStatus::Closed,
                [InternalAuditStatus::Reporting, InternalAuditStatus::FollowUp],
                'Close:InternalAudit',
                'success',
                milestoneFields: true,
            ),
            $this->transitionAction('cancel', 'Cancel', InternalAuditStatus::Cancelled, [
                InternalAuditStatus::Draft,
                InternalAuditStatus::Scheduled,
                InternalAuditStatus::InProgress,
            ], 'Manage:InternalAudit', 'danger'),
        ];
    }

    /** @param list<InternalAuditStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        InternalAuditStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
        bool $milestoneFields = false,
    ): Action {
        $schema = [];

        if ($milestoneFields) {
            $schema[] = DateTimePicker::make('report_issued_at')
                ->required(fn (): bool => $this->record->report_issued_at === null)
                ->default(fn () => $this->record->report_issued_at);
            if ($toStatus === InternalAuditStatus::FollowUp) {
                $schema[] = DatePicker::make('follow_up_due_at')
                    ->required(fn (): bool => $this->record->follow_up_due_at === null)
                    ->default(fn () => $this->record->follow_up_due_at);
            }
        }

        $schema[] = ApprovalNarrativeTextarea::decisionRationale(
            name: 'reason',
            label: 'Decision reason',
            helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
            context: fn (): array => [
                'record_type' => 'Internal Audit lifecycle decision',
                'subject' => $this->record->audit_number ?? (string) $this->record->getKey(),
                'decision' => $label,
            ],
        );

        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema($schema)
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label, $milestoneFields): void {
                /** @var User $user */
                $user = auth()->user();

                if ($milestoneFields) {
                    $updates = [];
                    if (filled($data['report_issued_at'] ?? null)) {
                        $updates['report_issued_at'] = $data['report_issued_at'];
                    }
                    if (filled($data['follow_up_due_at'] ?? null)) {
                        $updates['follow_up_due_at'] = $data['follow_up_due_at'];
                    }
                    if ($updates !== []) {
                        $this->record->update($updates);
                    }
                }

                app(InternalAuditTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'started_at', 'completed_at', 'closed_at', 'report_issued_at', 'follow_up_due_at']);

                Notification::make()->success()->title("Internal Audit: {$label}")->send();
            });
    }
}
