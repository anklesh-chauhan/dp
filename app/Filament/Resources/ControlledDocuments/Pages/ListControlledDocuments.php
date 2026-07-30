<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\Pages;

use App\Domain\Reporting\Enums\ReportScope;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\ReportTemplate;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListControlledDocuments extends ListRecords
{
    protected static string $resource = ControlledDocumentResource::class;

    protected function getActions(): array
    {
        return [
            Action::make('distributionReport')
                ->label('Distribution Report')
                ->icon(Heroicon::ArrowDownTray)
                ->schema([
                    Select::make('template')
                        ->label('Report Template & Format')
                        ->options(fn (): array => ReportTemplate::query()
                            ->active()
                            ->where('scope', ReportScope::DocumentDistribution)
                            ->get()
                            ->mapWithKeys(fn (ReportTemplate $template): array => [
                                $template->id => "{$template->name} ({$template->format->label()})",
                            ])
                            ->all())
                        ->required(),
                ])
                ->action(fn (array $data): mixed => $this->redirect(route('reports.document-distribution', [
                    'template' => $data['template'],
                ]))),
            CreateAction::make(),
        ];
    }
}
