<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentMaintenances\Pages;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Services\EquipmentMaintenanceTransitionService;
use App\Filament\Resources\EquipmentMaintenances\EquipmentMaintenanceResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewEquipmentMaintenance extends ViewRecord
{
    protected static string $resource = EquipmentMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => EquipmentMaintenanceResource::canEdit($this->record)),
            $this->transitionAction('beginWork', 'Begin Work', EquipmentMaintenanceStatus::InProgress, [
                EquipmentMaintenanceStatus::Planned,
            ], 'Perform:EquipmentMaintenance'),
            $this->completeAction(),
            $this->transitionAction('cancel', 'Cancel', EquipmentMaintenanceStatus::Cancelled, [
                EquipmentMaintenanceStatus::Planned,
                EquipmentMaintenanceStatus::InProgress,
            ], 'Manage:EquipmentMaintenance', 'danger'),
        ];
    }

    private function completeAction(): Action
    {
        return Action::make('complete')
            ->label('Complete')
            ->color('success')
            ->schema([
                Textarea::make('notes')->rows(3),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'Confirm the preventive maintenance work order was completed.',
                    context: fn (): array => [
                        'record_type' => 'Equipment maintenance completion',
                        'subject' => $this->record->work_order_number ?? (string) $this->record->getKey(),
                        'decision' => 'Complete',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->status === EquipmentMaintenanceStatus::InProgress
                && (bool) auth()->user()?->can('Perform:EquipmentMaintenance'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentMaintenanceTransitionService::class)->transition(
                    $this->record,
                    EquipmentMaintenanceStatus::Completed,
                    $user,
                    $data['reason'],
                    [
                        'notes' => $data['notes'] ?? null,
                    ],
                    request()->ip(),
                    request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'performed_by',
                    'completed_at',
                    'notes',
                ]);

                Notification::make()->success()->title('Equipment maintenance: Complete')->send();
            });
    }

    /** @param list<EquipmentMaintenanceStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        EquipmentMaintenanceStatus $toStatus,
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
                        'record_type' => 'Equipment maintenance lifecycle decision',
                        'subject' => $this->record->work_order_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentMaintenanceTransitionService::class)->transition(
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
                    'completed_at',
                ]);

                Notification::make()->success()->title("Equipment maintenance: {$label}")->send();
            });
    }
}
