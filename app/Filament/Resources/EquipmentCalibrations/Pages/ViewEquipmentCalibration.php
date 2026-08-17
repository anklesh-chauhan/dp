<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentCalibrations\Pages;

use App\Domain\QMS\Enums\EquipmentCalibrationResult;
use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Services\EquipmentCalibrationTransitionService;
use App\Filament\Resources\EquipmentCalibrations\EquipmentCalibrationResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewEquipmentCalibration extends ViewRecord
{
    protected static string $resource = EquipmentCalibrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => EquipmentCalibrationResource::canEdit($this->record)),
            $this->transitionAction('beginPerformance', 'Begin Performance', EquipmentCalibrationStatus::InProgress, [
                EquipmentCalibrationStatus::Scheduled,
            ], 'Perform:EquipmentCalibration'),
            $this->completeAction(),
            $this->outOfToleranceAction(),
            $this->verifyAction(),
            $this->transitionAction('cancel', 'Cancel', EquipmentCalibrationStatus::Cancelled, [
                EquipmentCalibrationStatus::Scheduled,
                EquipmentCalibrationStatus::InProgress,
            ], 'Manage:EquipmentCalibration', 'danger'),
        ];
    }

    private function completeAction(): Action
    {
        return Action::make('complete')
            ->label('Complete')
            ->color('success')
            ->schema([
                Select::make('result')
                    ->options([
                        EquipmentCalibrationResult::Pass->value => EquipmentCalibrationResult::Pass->getLabel(),
                        EquipmentCalibrationResult::Fail->value => EquipmentCalibrationResult::Fail->getLabel(),
                    ])
                    ->required(),
                TextInput::make('certificate_reference')->maxLength(255),
                DateTimePicker::make('next_due_at')->native(false),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'Record the calibration outcome. Out-of-tolerance results require the dedicated action with a deviation link.',
                    context: fn (): array => [
                        'record_type' => 'Equipment calibration completion',
                        'subject' => $this->record->calibration_number ?? (string) $this->record->getKey(),
                        'decision' => 'Complete',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->status === EquipmentCalibrationStatus::InProgress
                && (bool) auth()->user()?->can('Perform:EquipmentCalibration'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentCalibrationTransitionService::class)->transition(
                    $this->record,
                    EquipmentCalibrationStatus::Completed,
                    $user,
                    $data['reason'],
                    [
                        'result' => $data['result'],
                        'certificate_reference' => $data['certificate_reference'] ?? null,
                        'next_due_at' => $data['next_due_at'] ?? null,
                    ],
                    request()->ip(),
                    request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'result',
                    'performed_by',
                    'performed_at',
                    'next_due_at',
                    'certificate_reference',
                ]);

                Notification::make()->success()->title('Equipment calibration: Complete')->send();
            });
    }

    private function outOfToleranceAction(): Action
    {
        return Action::make('markOutOfTolerance')
            ->label('Mark Out of Tolerance')
            ->color('danger')
            ->schema([
                Select::make('deviation_id')
                    ->label('Linked deviation')
                    ->options(fn (): array => Deviation::query()
                        ->orderByDesc('id')
                        ->limit(100)
                        ->pluck('deviation_number', 'id')
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('certificate_reference')->maxLength(255),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'Out-of-tolerance calibrations must be linked to a deviation before close.',
                    context: fn (): array => [
                        'record_type' => 'Equipment calibration out of tolerance',
                        'subject' => $this->record->calibration_number ?? (string) $this->record->getKey(),
                        'decision' => 'Mark Out of Tolerance',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->status === EquipmentCalibrationStatus::InProgress
                && (bool) auth()->user()?->can('Perform:EquipmentCalibration'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentCalibrationTransitionService::class)->transition(
                    $this->record,
                    EquipmentCalibrationStatus::OutOfTolerance,
                    $user,
                    $data['reason'],
                    [
                        'deviation_id' => $data['deviation_id'],
                        'certificate_reference' => $data['certificate_reference'] ?? null,
                    ],
                    request()->ip(),
                    request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'result',
                    'performed_by',
                    'performed_at',
                    'deviation_id',
                    'certificate_reference',
                ]);

                Notification::make()->success()->title('Equipment calibration: Out of Tolerance')->send();
            });
    }

    private function verifyAction(): Action
    {
        return Action::make('verify')
            ->label('Verify')
            ->color('success')
            ->schema([
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Verification reason',
                    helperText: 'Confirm independent verification of the calibration record.',
                    context: fn (): array => [
                        'record_type' => 'Equipment calibration verification',
                        'subject' => $this->record->calibration_number ?? (string) $this->record->getKey(),
                        'decision' => 'Verify',
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, [
                EquipmentCalibrationStatus::Completed,
                EquipmentCalibrationStatus::OutOfTolerance,
            ], true)
                && $this->record->verified_by === null
                && (bool) auth()->user()?->can('Verify:EquipmentCalibration'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentCalibrationTransitionService::class)->verify(
                    $this->record,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['verified_by']);

                Notification::make()->success()->title('Equipment calibration: Verified')->send();
            });
    }

    /** @param list<EquipmentCalibrationStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        EquipmentCalibrationStatus $toStatus,
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
                        'record_type' => 'Equipment calibration lifecycle decision',
                        'subject' => $this->record->calibration_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentCalibrationTransitionService::class)->transition(
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
                    'performed_by',
                    'performed_at',
                    'result',
                ]);

                Notification::make()->success()->title("Equipment calibration: {$label}")->send();
            });
    }
}
