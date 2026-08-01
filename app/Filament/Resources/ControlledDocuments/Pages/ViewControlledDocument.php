<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\Pages;

use App\Actions\Sop\SubmitDocumentAction;
use App\Domain\DMS\Actions\CreateDocumentRevisionAction;
use App\Domain\DMS\Actions\LockDocumentAction;
use App\Domain\DMS\Actions\UnlockDocumentAction;
use App\Domain\DMS\Services\ControlledDocumentAccessService;
use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Shared\Services\AuditLogService;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\ProvidesRetentionLifecycleActions;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\DocumentStatus;
use App\Models\ReportTemplate;
use App\Models\SopAuditLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ViewControlledDocument extends ViewRecord
{
    use HandlesServiceExceptions;
    use ProvidesRetentionLifecycleActions;

    protected static string $resource = ControlledDocumentResource::class;

    protected function getActions(): array
    {
        return [
            ...$this->getDocumentRetentionLifecycleActions(),
            Action::make('createRevision')
                ->label('Create Revision')
                ->icon(Heroicon::DocumentDuplicate)
                ->color('warning')
                ->schema([
                    Textarea::make('revision_reason')
                        ->label('Reason for revision')
                        ->required()
                        ->maxLength(2000),
                ])
                ->visible(fn (): bool => in_array($this->record->documentStatus?->code, [
                    DocumentStatus::APPROVED,
                    DocumentStatus::EFFECTIVE,
                    DocumentStatus::OBSOLETE,
                ], true) && (Auth::user()?->can('revise', $this->record) ?? false))
                ->action(function (array $data): void {
                    $this->runServiceAction(
                        fn () => app(CreateDocumentRevisionAction::class)->execute(
                            $this->record,
                            Auth::user(),
                            $data['revision_reason'],
                        ),
                        failureTitle: 'Revision Failed',
                        successTitle: 'Draft revision created',
                        afterSuccess: fn ($revision) => $this->redirect(
                            ControlledDocumentResource::getUrl('edit', ['record' => $revision])
                        ),
                    );
                }),
            Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Submit this document to the department approval workflow. Editing will be locked once submitted.')
                ->visible(fn (): bool => $this->record->documentStatus?->hasCode(DocumentStatus::DRAFT)
                    && Auth::user()?->can('submit', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(SubmitDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Submission Failed',
                        successTitle: 'Document submitted for approval',
                        afterSuccess: fn () => $this->refreshFormData(['document_status_id', 'approvals']),
                    );
                }),
            Action::make('lockDocument')
                ->label('Lock for Editing')
                ->icon(Heroicon::LockClosed)
                ->visible(fn (): bool => $this->record->documentStatus?->hasCode(DocumentStatus::DRAFT)
                    && ! $this->record->isLocked()
                    && Auth::user()?->can('lock', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(LockDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Lock Failed',
                        successTitle: 'Document locked for editing',
                        afterSuccess: fn () => $this->refreshFormData(['locked_by', 'locked_at']),
                    );
                }),
            Action::make('unlockDocument')
                ->label('Unlock')
                ->icon(Heroicon::LockOpen)
                ->color('warning')
                ->visible(fn (): bool => $this->record->isLocked()
                    && Auth::user()?->can('unlock', $this->record))
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(UnlockDocumentAction::class)->execute($this->record, Auth::user()),
                        failureTitle: 'Unlock Failed',
                        successTitle: 'Document unlocked',
                        afterSuccess: fn () => $this->refreshFormData(['locked_by', 'locked_at']),
                    );
                }),
            Action::make('printPdf')
                ->label('View Controlled PDF')
                ->icon(Heroicon::Eye)
                ->schema([
                    Select::make('template')
                        ->label('Print Template')
                        ->options(fn (): array => ReportTemplate::query()
                            ->active()
                            ->where('scope', ReportScope::ControlledDocument)
                            ->where('format', ReportFormat::Pdf)
                            ->pluck('name', 'id')
                            ->all())
                        ->required(),
                ])
                ->action(fn (array $data): mixed => $this->redirect(route('controlled-documents.viewer', [
                    'controlledDocument' => $this->record,
                    'template' => $data['template'],
                ])))
                ->visible(fn (): bool => $this->record->canBePrintedDirectly()
                    && app(ControlledDocumentAccessService::class)->canView(Auth::user(), $this->record)),
            Action::make('managePdfAccess')
                ->label('Manage PDF Access')
                ->icon(Heroicon::Users)
                ->color('gray')
                ->schema([
                    Repeater::make('grants')
                        ->label('Shared With')
                        ->helperText('When this list is empty, normal role permissions apply. Once users are added, only active grants, document owners, and access managers can use the controlled viewer.')
                        ->schema([
                            Select::make('user_id')
                                ->label('User')
                                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                            Toggle::make('can_view')->label('View')->default(true),
                            Toggle::make('can_print')->label('Print'),
                            Toggle::make('can_download')->label('Download'),
                            DateTimePicker::make('expires_at')->label('Expires At')->seconds(false),
                        ])
                        ->columns(5)
                        ->defaultItems(0),
                ])
                ->fillForm(fn (): array => [
                    'grants' => $this->record->accessGrants()
                        ->get(['user_id', 'can_view', 'can_print', 'can_download', 'expires_at'])
                        ->map(fn ($grant): array => $grant->toArray())
                        ->all(),
                ])
                ->action(function (array $data): void {
                    DB::transaction(function () use ($data): void {
                        $grants = collect($data['grants'] ?? []);
                        $userIds = $grants->pluck('user_id')->filter()->map(fn ($id): int => (int) $id);

                        $this->record->accessGrants()->whereNotIn('user_id', $userIds)->delete();

                        foreach ($grants as $grant) {
                            $this->record->accessGrants()->updateOrCreate(
                                ['user_id' => (int) $grant['user_id']],
                                [
                                    'can_view' => (bool) ($grant['can_view'] ?? false),
                                    'can_print' => (bool) ($grant['can_print'] ?? false),
                                    'can_download' => (bool) ($grant['can_download'] ?? false),
                                    'expires_at' => $grant['expires_at'] ?? null,
                                    'granted_by' => Auth::id(),
                                ],
                            );
                        }
                    });

                    app(AuditLogService::class)->log(
                        action: SopAuditLog::ACTION_PDF_ACCESS_UPDATED,
                        newValues: [
                            'grants' => collect($data['grants'] ?? [])->map(fn (array $grant): array => [
                                'user_id' => (int) $grant['user_id'],
                                'can_view' => (bool) ($grant['can_view'] ?? false),
                                'can_print' => (bool) ($grant['can_print'] ?? false),
                                'can_download' => (bool) ($grant['can_download'] ?? false),
                                'expires_at' => $grant['expires_at'] ?? null,
                            ])->all(),
                        ],
                        document: $this->record,
                    );
                })
                ->visible(fn (): bool => app(ControlledDocumentAccessService::class)->canManage(Auth::user(), $this->record)),
            EditAction::make()
                ->visible(fn (): bool => Auth::user()?->can('update', $this->record) ?? false),
        ];
    }
}
