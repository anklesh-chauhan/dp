<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Actions\Sop\ArchiveTemplateAction;
use App\Actions\Sop\PublishTemplateAction;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Resources\SopTemplates\SopTemplateResource;
use App\Models\TemplateStatus;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSopTemplate extends EditRecord
{
    use HandlesServiceExceptions;

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
                    $this->runServiceAction(
                        fn () => app(PublishTemplateAction::class)->execute($this->record, (int) Auth::id(), $data['change_reason'] ?? null),
                        failureTitle: 'Publish Failed',
                        successTitle: 'Template published',
                    );
                }),
            Action::make('archive')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => Auth::user()?->can('archive', $this->record) ?? false)
                ->action(function (): void {
                    $this->runServiceAction(
                        fn () => app(ArchiveTemplateAction::class)->execute($this->record, (int) Auth::id()),
                        failureTitle: 'Archive Failed',
                        successTitle: 'Template archived',
                    );
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->templateStatus?->hasCode(TemplateStatus::PUBLISHED)) {
            $data['template_status_id'] = TemplateStatus::idFor(TemplateStatus::DRAFT);
            $data['current_version'] = $this->record->current_version;
        }

        return $data;
    }
}
