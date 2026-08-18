<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComputerizedSystemIncidents\Pages;

use App\Domain\QMS\Enums\ComputerizedSystemIncidentStatus;
use App\Domain\QMS\Services\ComputerizedSystemIncidentTransitionService;
use App\Filament\Resources\ComputerizedSystemIncidents\ComputerizedSystemIncidentResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewComputerizedSystemIncident extends ViewRecord
{
    protected static string $resource = ComputerizedSystemIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->transitionAction('beginInvestigation', 'Begin investigation', ComputerizedSystemIncidentStatus::Investigating, [ComputerizedSystemIncidentStatus::Open], 'Investigate:ComputerizedSystemIncident'),
            $this->transitionAction('resolve', 'Resolve', ComputerizedSystemIncidentStatus::Resolved, [ComputerizedSystemIncidentStatus::Investigating], 'Close:ComputerizedSystemIncident', 'success'),
            $this->transitionAction('close', 'Close', ComputerizedSystemIncidentStatus::Closed, [ComputerizedSystemIncidentStatus::Resolved], 'Close:ComputerizedSystemIncident', 'success'),
            $this->transitionAction('cancel', 'Cancel', ComputerizedSystemIncidentStatus::Cancelled, [ComputerizedSystemIncidentStatus::Open, ComputerizedSystemIncidentStatus::Investigating], 'Manage:ComputerizedSystemIncident', 'danger'),
        ];
    }

    /** @param  list<ComputerizedSystemIncidentStatus>  $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        ComputerizedSystemIncidentStatus $toStatus,
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
                        'record_type' => 'Computerized system incident',
                        'subject' => $this->record->incident_number,
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true) && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();
                app(ComputerizedSystemIncidentTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                Notification::make()->success()->title("Incident: {$label}")->send();
            });
    }
}
