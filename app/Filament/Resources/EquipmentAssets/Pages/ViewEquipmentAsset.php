<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\Pages;

use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Services\EquipmentAssetTransitionService;
use App\Filament\Resources\EquipmentAssets\EquipmentAssetResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewEquipmentAsset extends ViewRecord
{
    protected static string $resource = EquipmentAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => EquipmentAssetResource::canEdit($this->record)),
            $this->transitionAction('activate', 'Activate', EquipmentAssetStatus::Active, [
                EquipmentAssetStatus::Inactive,
            ], 'Update:EquipmentAsset', 'success'),
            $this->transitionAction('deactivate', 'Deactivate', EquipmentAssetStatus::Inactive, [
                EquipmentAssetStatus::Active,
            ], 'Update:EquipmentAsset', 'warning'),
            $this->transitionAction('decommission', 'Decommission', EquipmentAssetStatus::Decommissioned, [
                EquipmentAssetStatus::Active,
                EquipmentAssetStatus::Inactive,
            ], 'Manage:EquipmentAsset', 'danger'),
        ];
    }

    /** @param list<EquipmentAssetStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        EquipmentAssetStatus $toStatus,
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
                    helperText: 'Explain why this equipment status is changing.',
                    context: fn (): array => [
                        'record_type' => 'Equipment asset lifecycle decision',
                        'subject' => $this->record->asset_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(EquipmentAssetTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status']);

                Notification::make()->success()->title("Equipment asset: {$label}")->send();
            });
    }
}
