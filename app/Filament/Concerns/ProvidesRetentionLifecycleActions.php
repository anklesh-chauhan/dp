<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Actions\Sop\ArchiveDocumentAction;
use App\Actions\Sop\ArchiveTemplateAction;
use App\Actions\Sop\CompleteDocumentRetentionAction;
use App\Actions\Sop\CompleteTemplateRetentionAction;
use App\Actions\Sop\DestroyDocumentAction;
use App\Actions\Sop\DestroyTemplateAction;
use App\Actions\Sop\MarkDocumentObsoleteAction;
use App\Actions\Sop\MarkTemplateObsoleteAction;
use App\Models\DocumentStatus;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Auth;

trait ProvidesRetentionLifecycleActions
{
    /**
     * @return list<Action>
     */
    protected function getDocumentRetentionLifecycleActions(): array
    {
        /** @var SopDocument $record */
        $record = $this->record;

        return [
            Action::make('markObsolete')
                ->label('Mark Obsolete')
                ->color('warning')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Reason')->rows(2),
                ])
                ->visible(fn (): bool => ($record->documentStatus?->hasCode(DocumentStatus::EFFECTIVE)
                    || $record->documentStatus?->hasCode(DocumentStatus::APPROVED))
                    && (Auth::user()?->can('markObsolete', $record) ?? false))
                ->action(function (array $data): void {
                    $this->runRetentionAction(
                        fn () => app(MarkDocumentObsoleteAction::class)->execute($this->record, Auth::user(), $data['reason'] ?? null),
                        'Mark Obsolete Failed',
                        'Document marked obsolete',
                    );
                }),
            Action::make('archiveDocument')
                ->label('Archive')
                ->color('warning')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Reason')->rows(2),
                ])
                ->visible(fn (): bool => ($record->documentStatus?->hasCode(DocumentStatus::SUPERSEDED)
                    || $record->documentStatus?->hasCode(DocumentStatus::OBSOLETE))
                    && (Auth::user()?->can('archive', $record) ?? false))
                ->action(function (array $data): void {
                    $this->runRetentionAction(
                        fn () => app(ArchiveDocumentAction::class)->execute($this->record, Auth::user(), $data['reason'] ?? null),
                        'Archive Failed',
                        'Document archived',
                    );
                }),
            Action::make('completeDocumentRetention')
                ->label('Complete Retention')
                ->color('gray')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Reason')->rows(2),
                ])
                ->visible(fn (): bool => $record->documentStatus?->hasCode(DocumentStatus::ARCHIVED)
                    && (Auth::user()?->can('completeRetention', $record) ?? false))
                ->action(function (array $data): void {
                    $this->runRetentionAction(
                        fn () => app(CompleteDocumentRetentionAction::class)->execute($this->record, Auth::user(), $data['reason'] ?? null),
                        'Retention Completion Failed',
                        'Document retention completed',
                    );
                }),
            Action::make('destroyDocument')
                ->label('Destroy')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Destruction Reason')->required()->rows(2),
                ])
                ->visible(fn (): bool => $record->documentStatus?->hasCode(DocumentStatus::RETENTION_COMPLETED)
                    && (Auth::user()?->can('destroy', $record) ?? false))
                ->action(function (array $data): void {
                    $this->runRetentionAction(
                        fn () => app(DestroyDocumentAction::class)->execute($this->record, Auth::user(), $data['reason']),
                        'Destruction Failed',
                        'Document destroyed',
                    );
                }),
        ];
    }

    /**
     * @return list<Action>
     */
    protected function getTemplateRetentionLifecycleActions(): array
    {
        /** @var SopTemplate $record */
        $record = $this->record;

        return [
            Action::make('markTemplateObsolete')
                ->label('Mark Obsolete')
                ->color('warning')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Reason')->rows(2),
                ])
                ->visible(fn (): bool => $record->templateStatus?->hasCode(TemplateStatus::PUBLISHED)
                    && (Auth::user()?->can('markObsolete', $record) ?? false))
                ->action(function (array $data): void {
                    $this->runRetentionAction(
                        fn () => app(MarkTemplateObsoleteAction::class)->execute($this->record, Auth::user(), $data['reason'] ?? null),
                        'Mark Obsolete Failed',
                        'Template marked obsolete',
                    );
                }),
            Action::make('archiveTemplate')
                ->label('Archive')
                ->color('warning')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Reason')->rows(2),
                ])
                ->visible(fn (): bool => $record->templateStatus?->hasCode(TemplateStatus::OBSOLETE)
                    && (Auth::user()?->can('archive', $record) ?? false))
                ->action(function (array $data): void {
                    $this->runRetentionAction(
                        fn () => app(ArchiveTemplateAction::class)->execute($this->record, Auth::user(), $data['reason'] ?? null),
                        'Archive Failed',
                        'Template archived',
                    );
                }),
            Action::make('completeTemplateRetention')
                ->label('Complete Retention')
                ->color('gray')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Reason')->rows(2),
                ])
                ->visible(fn (): bool => $record->templateStatus?->hasCode(TemplateStatus::ARCHIVED)
                    && (Auth::user()?->can('completeRetention', $record) ?? false))
                ->action(function (array $data): void {
                    $this->runRetentionAction(
                        fn () => app(CompleteTemplateRetentionAction::class)->execute($this->record, Auth::user(), $data['reason'] ?? null),
                        'Retention Completion Failed',
                        'Template retention completed',
                    );
                }),
            Action::make('destroyTemplate')
                ->label('Destroy')
                ->color('danger')
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('Destruction Reason')->required()->rows(2),
                ])
                ->visible(fn (): bool => $record->templateStatus?->hasCode(TemplateStatus::RETENTION_COMPLETED)
                    && (Auth::user()?->can('destroy', $record) ?? false))
                ->action(function (array $data): void {
                    $this->runRetentionAction(
                        fn () => app(DestroyTemplateAction::class)->execute($this->record, Auth::user(), $data['reason']),
                        'Destruction Failed',
                        'Template destroyed',
                    );
                }),
        ];
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function runRetentionAction(callable $callback, string $failureTitle, string $successTitle): void
    {
        $this->runServiceAction(
            $callback,
            failureTitle: $failureTitle,
            successTitle: $successTitle,
            afterSuccess: fn () => $this->refreshFormData(['document_status_id', 'template_status_id']),
        );
    }
}
