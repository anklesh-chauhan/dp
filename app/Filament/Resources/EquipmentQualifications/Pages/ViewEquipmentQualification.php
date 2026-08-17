<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentQualifications\Pages;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Services\EquipmentQualificationTransitionService;
use App\Filament\Resources\EquipmentQualifications\EquipmentQualificationResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewEquipmentQualification extends ViewRecord
{
    protected static string $resource = EquipmentQualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => EquipmentQualificationResource::canEdit($this->record)),
            $this->transitionAction('beginExecution', 'Begin Execution', EquipmentQualificationStatus::InProgress, [
                EquipmentQualificationStatus::Draft,
            ], 'Execute:EquipmentQualification'),
            $this->transitionAction('resumeExecution', 'Return to Execution', EquipmentQualificationStatus::InProgress, [
                EquipmentQualificationStatus::UnderReview,
            ], 'Execute:EquipmentQualification'),
            $this->transitionAction('submitForReview', 'Submit for Review', EquipmentQualificationStatus::UnderReview, [
                EquipmentQualificationStatus::InProgress,
            ], 'Execute:EquipmentQualification'),
            $this->transitionAction('approve', 'Approve', EquipmentQualificationStatus::Approved, [
                EquipmentQualificationStatus::UnderReview,
            ], 'Approve:EquipmentQualification', 'success'),
            $this->failAction(),
            $this->transitionAction('cancel', 'Cancel', EquipmentQualificationStatus::Cancelled, [
                EquipmentQualificationStatus::Draft,
                EquipmentQualificationStatus::InProgress,
                EquipmentQualificationStatus::UnderReview,
            ], 'Manage:EquipmentQualification', 'danger'),
        ];
    }

    private function failAction(): Action
    {
        return Action::make('fail')
            ->label('Mark Failed')
            ->color('danger')
            ->schema([
                Select::make('deviation_id')
                    ->label('Linked deviation')
                    ->options(fn (): array => Deviation::query()
                        ->orderByDesc('id')
                        ->limit(100)
                        ->pluck('deviation_number', 'id')
                        ->all())
                    ->searchable(),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'Explain the failure. Optionally link an existing deviation.',
                    context: fn (): array => [
                        'record_type' => 'Equipment qualification failure',
                        'subject' => $this->record->qualification_number ?? (string) $this->record->getKey(),
                        'decision' => 'Mark Failed',
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, [
                EquipmentQualificationStatus::InProgress,
                EquipmentQualificationStatus::UnderReview,
            ], true) && (bool) auth()->user()?->can('Review:EquipmentQualification'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentQualificationTransitionService::class)->transition(
                    $this->record,
                    EquipmentQualificationStatus::Failed,
                    $user,
                    $data['reason'],
                    [
                        'deviation_id' => $data['deviation_id'] ?? null,
                    ],
                    request()->ip(),
                    request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'reviewed_by',
                    'completed_at',
                    'deviation_id',
                ]);

                Notification::make()->success()->title('Equipment qualification: Mark Failed')->send();
            });
    }

    /** @param list<EquipmentQualificationStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        EquipmentQualificationStatus $toStatus,
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
                        'record_type' => 'Equipment qualification lifecycle decision',
                        'subject' => $this->record->qualification_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentQualificationTransitionService::class)->transition(
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
                    'executed_by',
                    'reviewed_by',
                    'started_at',
                    'completed_at',
                    'approved_at',
                ]);

                Notification::make()->success()->title("Equipment qualification: {$label}")->send();
            });
    }
}
