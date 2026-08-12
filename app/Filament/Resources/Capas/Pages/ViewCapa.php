<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas\Pages;

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Services\CapaTransitionService;
use App\Filament\Resources\Capas\CapaResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewCapa extends ViewRecord
{
    protected static string $resource = CapaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => CapaResource::canEdit($this->record)),
            $this->transitionAction('plan', 'Approve Plan', CapaStatus::Planned, [CapaStatus::Draft], 'Update:Capa'),
            $this->transitionAction('beginImplementation', 'Begin Implementation', CapaStatus::InProgress, [CapaStatus::Planned, CapaStatus::Ineffective], 'Implement:Capa'),
            $this->transitionAction('completeImplementation', 'Complete Implementation', CapaStatus::PendingEffectiveness, [CapaStatus::InProgress], 'Implement:Capa'),
            $this->transitionAction('markEffective', 'Mark Effective', CapaStatus::Effective, [CapaStatus::PendingEffectiveness], 'VerifyEffectiveness:Capa', 'success', true),
            $this->transitionAction('markIneffective', 'Mark Ineffective', CapaStatus::Ineffective, [CapaStatus::PendingEffectiveness], 'VerifyEffectiveness:Capa', 'danger', true),
            $this->transitionAction('close', 'Close', CapaStatus::Closed, [CapaStatus::Effective], 'Close:Capa', 'success'),
            $this->transitionAction('cancel', 'Cancel', CapaStatus::Cancelled, [CapaStatus::Draft, CapaStatus::Planned, CapaStatus::InProgress, CapaStatus::PendingEffectiveness, CapaStatus::Ineffective], 'Manage:Capa', 'danger'),
        ];
    }

    /** @param list<CapaStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        CapaStatus $toStatus,
        array $fromStatuses,
        string $permission,
        string $color = 'primary',
        bool $requiresEffectivenessResult = false,
    ): Action {
        $schema = [
            ApprovalNarrativeTextarea::decisionRationale(
                name: 'reason',
                label: 'Decision reason',
                helperText: 'Explain what you reviewed and why you are making this decision. This text becomes part of the signed approval record.',
                context: fn (): array => [
                    'record_type' => 'CAPA lifecycle decision',
                    'subject' => $this->record->capa_number ?? (string) $this->record->getKey(),
                    'decision' => $label,
                    'extra' => filled($this->record->title)
                        ? 'Title: '.$this->record->title
                        : null,
                ],
            ),
        ];

        if ($requiresEffectivenessResult) {
            $schema[] = Textarea::make('effectiveness_result')
                ->label('Effectiveness Result')
                ->required()
                ->maxLength(5_000);
        }

        return Action::make($name)
            ->label($label)
            ->color($color)
            ->schema($schema)
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(CapaTransitionService::class)->transition(
                    $this->record,
                    $toStatus,
                    $user,
                    $data['reason'],
                    $data['effectiveness_result'] ?? null,
                    ipAddress: request()->ip(),
                    userAgent: request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData([
                    'status',
                    'completed_at',
                    'effectiveness_verified_at',
                    'effectiveness_result',
                    'closed_at',
                ]);

                Notification::make()->success()->title("CAPA: {$label}")->send();
            });
    }
}
