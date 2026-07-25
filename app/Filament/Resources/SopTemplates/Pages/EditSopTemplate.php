<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Domain\DMS\Actions\PublishTemplateAction;
use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\HasGenerationPolling;
use App\Filament\Concerns\ProcessesSopTemplateMetadataAi;
use App\Filament\Resources\SopTemplates\SopTemplateResource;
use App\Models\TemplateStatus;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSopTemplate extends EditRecord
{
    use HandlesServiceExceptions;
    use HasGenerationPolling;
    use ProcessesSopTemplateMetadataAi;

    protected static string $resource = SopTemplateResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! Auth::user()?->can('update', $this->record)) {
            Notification::make()
                ->title('Template is not editable')
                ->body('Archived templates and templates in retention cannot be edited.')
                ->danger()
                ->send();

            $this->redirect(SopTemplateResource::getUrl('view', ['record' => $this->record]));
        }
    }

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
            DeleteAction::make(),
        ];
    }

    /**
     * @var array<int>
     */
    private array $regulationTagIds = [];

    protected function beforeSave(): void
    {
        $state = $this->form->getRawState();

        $this->regulationTagIds = array_values(
            array_map(
                'intval',
                $state['regulationTags'] ?? [],
            ),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (
            $this->record
                ->templateStatus
                ?->hasCode(TemplateStatus::PUBLISHED)
        ) {
            $data['template_status_id'] = TemplateStatus::idFor(
                TemplateStatus::DRAFT,
            );

            $data['current_version'] = $this->record->current_version;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record
            ->regulationTags()
            ->sync($this->regulationTagIds);
    }
}
