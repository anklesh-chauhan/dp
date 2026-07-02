<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogDocuments\Pages;

use App\Actions\Sop\IssueDocumentAction;
use App\Actions\Sop\LockDocumentAction;
use App\Actions\Sop\SubmitDocumentAction;
use App\Actions\Sop\UnlockDocumentAction;
use App\Enums\DocumentStatus;
use App\Filament\Resources\LogDocuments\LogDocumentResource;
use App\Models\Department;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ViewLogDocument extends ViewRecord
{
    protected static string $resource = LogDocumentResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('submitForApproval')
                ->label('Submit for Approval')
                ->icon(Heroicon::PaperAirplane)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === DocumentStatus::Draft
                    && Auth::user()?->can('submit', $this->record))
                ->action(function (): void {
                    app(SubmitDocumentAction::class)->execute($this->record, Auth::user());

                    Notification::make()->title('Log document submitted for approval')->success()->send();
                    $this->refreshFormData(['status']);
                }),
            Action::make('issueControlledCopy')
                ->label('Issue Controlled Copy')
                ->icon(Heroicon::DocumentCheck)
                ->color('primary')
                ->schema([
                    Select::make('issued_to_user_id')
                        ->label('Issue To User')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    Select::make('issued_to_department_id')
                        ->label('Issue To Department')
                        ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                    TextInput::make('issued_to_location')->label('Issue To Location')->maxLength(255),
                    Textarea::make('notes')->rows(2),
                ])
                ->visible(fn (): bool => $this->record->canBeIssued()
                    && (Auth::user()?->can('Issue:DocumentIssuance') ?? false))
                ->action(function (array $data): void {
                    $issuance = app(IssueDocumentAction::class)->execute($this->record, Auth::user(), $data);

                    Notification::make()
                        ->title('Controlled copy issued')
                        ->body("Copy {$issuance->issuance_number} has been issued.")
                        ->success()
                        ->send();
                }),
            Action::make('lockDocument')
                ->label('Lock for Editing')
                ->icon(Heroicon::LockClosed)
                ->visible(fn (): bool => $this->record->status === DocumentStatus::Draft
                    && ! $this->record->isLocked()
                    && Auth::user()?->can('lock', $this->record))
                ->action(function (): void {
                    app(LockDocumentAction::class)->execute($this->record, Auth::user());
                    Notification::make()->title('Document locked')->success()->send();
                }),
            Action::make('unlockDocument')
                ->label('Unlock')
                ->icon(Heroicon::LockOpen)
                ->color('warning')
                ->visible(fn (): bool => $this->record->isLocked()
                    && Auth::user()?->can('unlock', $this->record))
                ->action(function (): void {
                    app(UnlockDocumentAction::class)->execute($this->record, Auth::user());
                    Notification::make()->title('Document unlocked')->success()->send();
                }),
            EditAction::make()
                ->visible(fn (): bool => Auth::user()?->can('update', $this->record) ?? false),
        ];
    }
}
