<?php

namespace App\Filament\Resources\ReportTemplates\Pages;

use App\Filament\Resources\ReportTemplates\ReportTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditReportTemplate extends EditRecord
{
    protected static string $resource = ReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon(Heroicon::Eye)
                ->url(fn (): string => route('report-templates.preview', $this->record))
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->format->value === 'pdf'),
            DeleteAction::make(),
        ];
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['page_settings'] = $this->record->printPageSettings();
        $data['header_zones'] = $this->record->printHeaderZones();
        $data['footer_zones'] = $this->record->printFooterZones();

        return $data;
    }
}
