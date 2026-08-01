<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\Pages;

use App\Enums\ProductModule;
use App\Filament\Concerns\ProcessesDocumentTemplateMetadataAi;
use App\Filament\Resources\DocumentTemplates\DocumentTemplateResource;
use App\Jobs\GenerateRegulatedTemplateJob;
use App\Models\TemplateStatus;
use App\Support\Modules\ModuleManager;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDocumentTemplate extends CreateRecord
{
    use ProcessesDocumentTemplateMetadataAi;

    protected static string $resource = DocumentTemplateResource::class;

    public bool $shouldGenerateWithAi = false;

    /**
     * @return array<int, Action>
     */
    protected function getFormActions(): array
    {
        return [
            parent::getCreateFormAction(),
            Action::make('createWithAi')
                ->label('Create with AI')
                ->icon('heroicon-m-sparkles')
                ->color('gray')
                ->action('createWithAi'),
            parent::getCancelFormAction(),
        ];
    }

    public function createWithAi(): void
    {
        if (! app(ModuleManager::class)->enabled(ProductModule::AI)) {
            Notification::make()
                ->danger()
                ->title('AI generation unavailable')
                ->body('Enable the AI module before creating a template with AI.')
                ->send();

            return;
        }

        $this->shouldGenerateWithAi = true;
        $this->create();
    }

    protected function getRedirectUrl(): string
    {
        return DocumentTemplateResource::getUrl('view', [
            'record' => $this->record,
        ]);
    }

    /**
     * @var array<int>
     */
    private array $regulationTagIds = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->regulationTagIds = array_values(
            array_map(
                'intval',
                $data['regulationTags'] ?? [],
            ),
        );

        unset($data['regulationTags']);

        $data['created_by'] = Auth::id();

        $data['template_status_id'] ??= TemplateStatus::idFor(
            TemplateStatus::DRAFT,
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $template = $this->record;

        $template->regulationTags()->sync(
            $this->regulationTagIds,
        );

        $template->load('regulationTags');

        if (
            ! $this->shouldGenerateWithAi
            || ! app(ModuleManager::class)->enabled(ProductModule::AI)
        ) {
            Notification::make()
                ->title('Template created')
                ->body('Add a draft version, sections, and variables to complete the template.')
                ->success()
                ->send();

            return;
        }

        $regulationTags = $template->regulationTags
            ->pluck('name')
            ->implode(', ');

        $template->update([
            'generation_status' => $template::GENERATION_STATUS_PROCESSING,

            'generation_progress' => 0,
        ]);

        GenerateRegulatedTemplateJob::dispatch(
            $template,
            $regulationTags,
        );

        Notification::make()
            ->title('Template Creation Started')
            ->body(
                'The template details were saved successfully. '
                .'The AI is now generating the regulatory template structure in the background.'
            )
            ->info()
            ->send();
    }
}
