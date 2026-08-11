<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\Pages;

use App\Filament\Concerns\HandlesServiceExceptions;
use App\Filament\Concerns\HasGenerationPolling;
use App\Filament\Concerns\ProcessesDocumentTemplateMetadataAi;
use App\Filament\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Models\DocumentTemplateVersion;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
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
                ->body('Published templates without a draft revision cannot be edited. Create a draft revision from the view page first.')
                ->danger()
                ->send();

            $this->redirect(DocumentTemplateResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getActions(): array
    {
        return [
            Action::make('previewWithPrintTemplate')
                ->label('Print Preview')
                ->icon(Heroicon::Eye)
                ->tooltip('Opens the print layout for review. This is not controlled printing.')
                ->url(fn (): string => route('document-templates.versions.preview', [
                    'documentTemplate' => $this->record,
                    'documentTemplateVersion' => $this->draftVersion(),
                ]))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->draftVersion() !== null
                    && $this->record->report_template_id !== null),
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

    protected function afterSave(): void
    {
        $this->record
            ->regulationTags()
            ->sync($this->regulationTagIds);
    }

    private function draftVersion(): ?DocumentTemplateVersion
    {
        return $this->record->latestDraftVersion()->first();
    }
}
