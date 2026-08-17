<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductRecalls\Pages;

use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Services\ProductRecallTransitionService;
use App\Filament\Resources\ProductRecalls\ProductRecallResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewProductRecall extends ViewRecord
{
    protected static string $resource = ProductRecallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => ProductRecallResource::canEdit($this->record)),
            $this->transitionAction('initiate', 'Initiate', ProductRecallStatus::Initiated, [ProductRecallStatus::Draft], 'Initiate:ProductRecall'),
            $this->classifyAction(),
            $this->transitionAction('notify', 'Begin Notification', ProductRecallStatus::NotificationInProgress, [ProductRecallStatus::RiskClassified], 'Notify:ProductRecall'),
            $this->transitionAction('execute', 'Begin Execution', ProductRecallStatus::ExecutionInProgress, [ProductRecallStatus::NotificationInProgress], 'Execute:ProductRecall'),
            $this->verifyAction(),
            $this->transitionAction('close', 'Close', ProductRecallStatus::Closed, [ProductRecallStatus::EffectivenessCheck], 'Close:ProductRecall', 'success'),
            $this->transitionAction('cancel', 'Cancel', ProductRecallStatus::Cancelled, [
                ProductRecallStatus::Draft,
                ProductRecallStatus::Initiated,
                ProductRecallStatus::RiskClassified,
                ProductRecallStatus::NotificationInProgress,
                ProductRecallStatus::ExecutionInProgress,
                ProductRecallStatus::EffectivenessCheck,
            ], 'Manage:ProductRecall', 'danger'),
        ];
    }

    private function classifyAction(): Action
    {
        return Action::make('classify')
            ->label('Classify Risk')
            ->schema([
                Select::make('classification')
                    ->options(collect(ProductRecallClassification::cases())
                        ->reject(fn (ProductRecallClassification $case): bool => $case === ProductRecallClassification::NotClassified)
                        ->mapWithKeys(fn (ProductRecallClassification $case): array => [$case->value => $case->getLabel()])
                        ->all())
                    ->required(),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'Explain the classification decision. This text becomes part of the signed approval record.',
                    context: fn (): array => [
                        'record_type' => 'Product recall classification',
                        'subject' => $this->record->recall_number ?? (string) $this->record->getKey(),
                        'decision' => 'Classify Risk',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->status === ProductRecallStatus::Initiated
                && (bool) auth()->user()?->can('Classify:ProductRecall'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(ProductRecallTransitionService::class)->transition(
                    $this->record,
                    ProductRecallStatus::RiskClassified,
                    $user,
                    $data['reason'],
                    ['classification' => $data['classification']],
                    request()->ip(),
                    request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'classification', 'classified_at']);

                Notification::make()->success()->title('Product recall: Classify Risk')->send();
            });
    }

    private function verifyAction(): Action
    {
        return Action::make('verify')
            ->label('Verify Effectiveness')
            ->schema([
                Textarea::make('effectiveness_summary')
                    ->label('Effectiveness summary')
                    ->required()
                    ->rows(4),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'Explain what you verified. This text becomes part of the signed approval record.',
                    context: fn (): array => [
                        'record_type' => 'Product recall effectiveness',
                        'subject' => $this->record->recall_number ?? (string) $this->record->getKey(),
                        'decision' => 'Verify Effectiveness',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->status === ProductRecallStatus::ExecutionInProgress
                && (bool) auth()->user()?->can('Verify:ProductRecall'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(ProductRecallTransitionService::class)->transition(
                    $this->record,
                    ProductRecallStatus::EffectivenessCheck,
                    $user,
                    $data['reason'],
                    ['effectiveness_summary' => $data['effectiveness_summary']],
                    request()->ip(),
                    request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'effectiveness_summary', 'effectiveness_verified_at']);

                Notification::make()->success()->title('Product recall: Verify Effectiveness')->send();
            });
    }

    /** @param list<ProductRecallStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        ProductRecallStatus $toStatus,
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
                        'record_type' => 'Product recall lifecycle decision',
                        'subject' => $this->record->recall_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(ProductRecallTransitionService::class)->transition(
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
                    'initiated_at',
                    'notified_at',
                    'executed_at',
                    'closed_at',
                ]);

                Notification::make()->success()->title("Product recall: {$label}")->send();
            });
    }
}
