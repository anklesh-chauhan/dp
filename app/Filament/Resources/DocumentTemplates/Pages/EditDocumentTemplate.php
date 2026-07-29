<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\Pages;

use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\HasGenerationPolling;
use App\Filament\Concerns\ProcessesDocumentTemplateMetadataAi;
use App\Filament\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Models\TemplateStatus;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditDocumentTemplate extends EditRecord
{
    use HandlesServiceExceptions;
    use HasGenerationPolling;
    use ProcessesDocumentTemplateMetadataAi;

    protected static string $resource = DocumentTemplateResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (
            ! Auth::user()?->can('update', $this->record)
            || ! $this->record->canBeEditedBy(Auth::user())
        ) {
            Notification::make()
                ->title('Template is not editable')
                ->body('Archived templates and templates in retention cannot be edited.')
                ->danger()
                ->send();

            $this->redirect(DocumentTemplateResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getActions(): array
    {
        return [
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
