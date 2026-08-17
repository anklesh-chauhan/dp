<?php

declare(strict_types=1);

namespace App\Filament\Resources\ValidationMasterPlans\Pages;

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Services\ValidationMasterPlanTransitionService;
use App\Filament\Resources\ValidationMasterPlans\ValidationMasterPlanResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewValidationMasterPlan extends ViewRecord
{
    protected static string $resource = ValidationMasterPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => ValidationMasterPlanResource::canEdit($this->record)),
            $this->transitionAction('activate', 'Activate', ValidationMasterPlanStatus::Active, [
                ValidationMasterPlanStatus::Draft,
                ValidationMasterPlanStatus::UnderRevision,
            ], 'Approve:ValidationMasterPlan', 'success'),
            $this->transitionAction('beginRevision', 'Begin Revision', ValidationMasterPlanStatus::UnderRevision, [
                ValidationMasterPlanStatus::Active,
            ], 'Update:ValidationMasterPlan'),
            $this->transitionAction('retire', 'Retire', ValidationMasterPlanStatus::Retired, [
                ValidationMasterPlanStatus::Active,
                ValidationMasterPlanStatus::UnderRevision,
            ], 'Retire:ValidationMasterPlan', 'warning'),
            $this->transitionAction('cancel', 'Cancel', ValidationMasterPlanStatus::Cancelled, [
                ValidationMasterPlanStatus::Draft,
                ValidationMasterPlanStatus::UnderRevision,
            ], 'Manage:ValidationMasterPlan', 'danger'),
        ];
    }

    /** @param list<ValidationMasterPlanStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        ValidationMasterPlanStatus $toStatus,
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
                        'record_type' => 'Validation master plan lifecycle decision',
                        'subject' => $this->record->vmp_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(ValidationMasterPlanTransitionService::class)->transition(
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
                    'approved_by',
                    'approved_at',
                    'retired_at',
                ]);

                Notification::make()->success()->title("Validation master plan: {$label}")->send();
            });
    }
}
