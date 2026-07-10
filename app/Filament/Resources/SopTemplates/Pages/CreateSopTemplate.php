<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Filament\Resources\SopTemplates\SopTemplateResource;
use App\Jobs\GenerateRegulatedTemplateJob;
use App\Models\TemplateStatus;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSopTemplate extends CreateRecord
{
    protected static string $resource = SopTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['template_status_id'] ??= TemplateStatus::idFor(TemplateStatus::DRAFT);

        return $data;
    }

    protected function afterCreate(): void
    {
        $template = $this->record->loadMissing('regulationTags');

        $regulationTags = $template->regulationTags
            ->pluck('name')
            ->implode(', ');

        if ($regulationTags === '') {
            Notification::make()
                ->title('Regulation tags required')
                ->body('Select at least one regulation tag before generating the template structure.')
                ->danger()
                ->send();

            return;
        }

        $template->update([
            'generation_status' => $template::GENERATION_STATUS_PROCESSING,
            'generation_progress' => 0,
        ]);

        GenerateRegulatedTemplateJob::dispatch($template, $regulationTags);

        // Notify the user instantly that processing has started in the background
        Notification::make()
            ->title('Template Creation Started')
            ->body('The root details are saved. The AI is now structuring the regulatory sections in the background. Refresh in a few moments.')
            ->info()
            ->send();
    }
}
