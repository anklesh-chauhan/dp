<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\Pages;

use App\Domain\DMS\Services\DocumentImportService;
use App\Domain\Reporting\Enums\ReportScope;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\ReportTemplate;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListControlledDocuments extends ListRecords
{
    protected static string $resource = ControlledDocumentResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),

            ActionGroup::make([
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
                Action::make('importDocuments')
                    ->label('Import Documents')
                    ->icon(Heroicon::ArrowUpTray)
                    ->schema([
                        TextInput::make('name')->label('Import batch name')->maxLength(255),
                        FileUpload::make('files')
                            ->label('PDF or Word files')
                            ->disk('local')
                            ->directory('imports/uploads')
                            ->visibility('private')
                            ->multiple()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(50_000)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $paths = array_values(array_filter((array) ($data['files'] ?? []), 'is_string'));
                        app(DocumentImportService::class)->importFiles($paths, auth()->user(), $data['name'] ?? null);
                    }),
                Action::make('importDocumentZip')
                    ->label('Import ZIP Batch')
                    ->icon(Heroicon::ArchiveBoxArrowDown)
                    ->schema([
                        TextInput::make('name')->label('Import batch name')->maxLength(255),
                        FileUpload::make('archive')
                            ->label('ZIP archive')
                            ->disk('local')
                            ->directory('imports/uploads')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                            ->maxSize(500_000)
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        app(DocumentImportService::class)->importZip((string) $data['archive'], auth()->user(), $data['name'] ?? null);
                    }),

            ]),


        ];
    }
}
