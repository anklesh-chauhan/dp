<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductReturns\Pages;

use App\Domain\QMS\Enums\ProductReturnDisposition;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Services\ProductReturnTransitionService;
use App\Filament\Resources\ProductReturns\ProductReturnResource;
use App\Filament\Support\ApprovalNarrativeTextarea;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

final class ViewProductReturn extends ViewRecord
{
    protected static string $resource = ProductReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn (): bool => ProductReturnResource::canEdit($this->record)),
            $this->transitionAction('receive', 'Mark Received', ProductReturnStatus::Received, [ProductReturnStatus::Draft], 'Receive:ProductReturn'),
            $this->transitionAction('quarantine', 'Place in Quarantine', ProductReturnStatus::UnderQuarantine, [ProductReturnStatus::Received], 'Quarantine:ProductReturn'),
            $this->disposeAction(),
            $this->transitionAction('close', 'Close', ProductReturnStatus::Closed, [ProductReturnStatus::DispositionPending], 'Close:ProductReturn', 'success'),
            $this->transitionAction('cancel', 'Cancel', ProductReturnStatus::Cancelled, [
                ProductReturnStatus::Draft,
                ProductReturnStatus::Received,
                ProductReturnStatus::UnderQuarantine,
                ProductReturnStatus::DispositionPending,
            ], 'Manage:ProductReturn', 'danger'),
        ];
    }

    private function disposeAction(): Action
    {
        return Action::make('dispose')
            ->label('QA Disposition')
            ->schema([
                Select::make('disposition')
                    ->options(collect(ProductReturnDisposition::cases())
                        ->reject(fn (ProductReturnDisposition $case): bool => $case === ProductReturnDisposition::Pending)
                        ->mapWithKeys(fn (ProductReturnDisposition $case): array => [$case->value => $case->getLabel()])
                        ->all())
                    ->required(),
                Textarea::make('qa_disposition_notes')
                    ->label('QA disposition notes')
                    ->rows(3),
                ApprovalNarrativeTextarea::decisionRationale(
                    name: 'reason',
                    label: 'Decision reason',
                    helperText: 'Explain the QA disposition. This text becomes part of the signed approval record.',
                    context: fn (): array => [
                        'record_type' => 'Product return QA disposition',
                        'subject' => $this->record->return_number ?? (string) $this->record->getKey(),
                        'decision' => 'QA Disposition',
                    ],
                ),
            ])
            ->visible(fn (): bool => $this->record->status === ProductReturnStatus::UnderQuarantine
                && (bool) auth()->user()?->can('Dispose:ProductReturn'))
            ->action(function (array $data): void {
                /** @var User $user */
                $user = auth()->user();

                app(ProductReturnTransitionService::class)->transition(
                    $this->record,
                    ProductReturnStatus::DispositionPending,
                    $user,
                    $data['reason'],
                    [
                        'disposition' => $data['disposition'],
                        'qa_disposition_notes' => $data['qa_disposition_notes'] ?? null,
                    ],
                    request()->ip(),
                    request()->userAgent(),
                );
                $this->record->refresh();
                $this->refreshFormData(['status', 'disposition', 'qa_disposition_notes', 'dispositioned_at']);

                Notification::make()->success()->title('Product return: QA Disposition')->send();
            });
    }

    /** @param list<ProductReturnStatus> $fromStatuses */
    private function transitionAction(
        string $name,
        string $label,
        ProductReturnStatus $toStatus,
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
                        'record_type' => 'Product return lifecycle decision',
                        'subject' => $this->record->return_number ?? (string) $this->record->getKey(),
                        'decision' => $label,
                    ],
                ),
            ])
            ->visible(fn (): bool => in_array($this->record->status, $fromStatuses, true)
                && (bool) auth()->user()?->can($permission))
            ->action(function (array $data) use ($toStatus, $label): void {
                /** @var User $user */
                $user = auth()->user();

                app(ProductReturnTransitionService::class)->transition(
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
                    'disposition',
                    'received_at',
                    'quarantined_at',
                    'closed_at',
                ]);

                Notification::make()->success()->title("Product return: {$label}")->send();
            });
    }
}
