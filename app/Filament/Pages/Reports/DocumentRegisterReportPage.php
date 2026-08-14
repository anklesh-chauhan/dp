<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\ProductModule;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Filament\Support\DocumentStatusColor;
use App\Models\ControlledDocument;
use App\Support\Formatting\DateFormatSettings;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentRegisterReportPage extends OperationalReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Document Register';

    protected static ?string $title = 'Document Register';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'dms-reports/document-register';

    public static function productModule(): ProductModule
    {
        return ProductModule::DMS;
    }

    public static function reportPermission(): string
    {
        return 'ViewAny:ControlledDocument';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->columns([
                TextColumn::make('document_number')->label('Document #')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('version')->sortable(),
                TextColumn::make('documentType.name')->label('Type')->badge(),
                TextColumn::make('department.name')->label('Department')->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->toggleable(),
                TextColumn::make('documentStatus.name')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ControlledDocument $record): string => $record->displayStatusLabel())
                    ->color(fn (ControlledDocument $record): string => DocumentStatusColor::for($record->documentStatus?->code)),
                TextColumn::make('effective_date')->date()->sortable(),
                TextColumn::make('review_date')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('document_status_id')->relationship('documentStatus', 'name')->label('Status'),
                SelectFilter::make('document_type_id')->relationship('documentType', 'name')->label('Type'),
                SelectFilter::make('department_id')->relationship('department', 'name')->label('Department'),
            ])
            ->defaultSort('document_number')
            ->recordUrl(fn (ControlledDocument $record): string => ControlledDocumentResource::getUrl('view', ['record' => $record]))
            ->headerActions([$this->exportCsvAction()])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No controlled documents')
            ->emptyStateDescription('The document register is empty for the current filters.');
    }

    protected function exportFilename(): string
    {
        return 'document-register-'.now()->format('Y-m-d-His');
    }

    protected function exportHeaders(): array
    {
        return ['Document #', 'Title', 'Version', 'Type', 'Department', 'Owner', 'Status', 'Effective Date', 'Review Date'];
    }

    protected function exportRows(): iterable
    {
        $dates = app(DateFormatSettings::class);

        foreach ($this->reportQuery()->lazyById() as $document) {
            yield [
                $document->document_number,
                $document->title,
                $document->version,
                $document->documentType?->name,
                $document->department?->name,
                $document->owner?->name,
                $document->displayStatusLabel(),
                $dates->formatDate($document->effective_date),
                $dates->formatDate($document->review_date),
            ];
        }
    }

    /**
     * @return Builder<ControlledDocument>
     */
    private function reportQuery(): Builder
    {
        return ControlledDocument::query()->with([
            'department',
            'documentStatus',
            'documentType',
            'owner',
            'approvals.workflowStep.approvalStepType',
            'approvals.workflowStep.role',
            'approvals.workflowStep.department',
            'approvals.approvalDecision',
        ]);
    }
}
