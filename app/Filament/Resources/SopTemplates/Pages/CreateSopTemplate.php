<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Filament\Concerns\ClassifiesSopTemplateFromMetadata;
use App\Filament\Resources\SopTemplates\SopTemplateResource;
use App\Jobs\GenerateRegulatedTemplateJob;
use App\Models\TemplateStatus;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSopTemplate extends CreateRecord
{
    use ClassifiesSopTemplateFromMetadata;

    protected static string $resource = SopTemplateResource::class;

    /**
     * @var array<int>
     */
    private array $regulationTagIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->regulationTagIds = array_map(
            'intval',
            $data['regulationTags'] ?? [],
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

        $template->regulationTags()->sync($this->regulationTagIds);

        $template->load('regulationTags');

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
