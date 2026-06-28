<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Actions\Sop\ArchiveTemplateAction;
use App\Actions\Sop\PublishTemplateAction;
use App\Enums\TemplateStatus;
use App\Filament\Resources\SopTemplates\SopTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSopTemplate extends EditRecord
{
    protected static string $resource = SopTemplateResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('publish')
                ->schema([
                    Textarea::make('change_reason')->required(),
                ])
                ->visible(fn (): bool => Auth::user()?->can('publish', $this->record) ?? false)
                ->action(function (array $data): void {
                    app(PublishTemplateAction::class)->execute($this->record, (int) Auth::id(), $data['change_reason'] ?? null);
                    Notification::make()->title('Template published')->success()->send();
                }),
            Action::make('archive')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('archive', $this->record) ?? false)
                ->action(function (): void {
                    app(ArchiveTemplateAction::class)->execute($this->record, (int) Auth::id());
                    Notification::make()->title('Template archived')->success()->send();
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->status === TemplateStatus::Published) {
            $data['status'] = TemplateStatus::Draft->value;
            $data['current_version'] = $this->record->current_version;
        }

        return $data;
    }
}
