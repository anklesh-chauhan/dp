<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\ProductModule;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Filament\Support\DocumentStatusColor;
use App\Models\ControlledDocument;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SopWhereUsedReportPage extends OperationalReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?string $navigationLabel = 'SOP Where-Used';

    protected static ?string $title = 'SOP Where-Used';

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'dms-reports/sop-where-used';

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
                TextColumn::make('referenced_sop_number')
                    ->label('Referenced SOP #')
                    ->state(fn (ControlledDocument $record): string => $record->referencedSop?->document_number ?? $record->referenced_sop_number ?? '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('referencedSop.title')
                    ->label('SOP title')
                    ->limit(36)
                    ->toggleable(),
                TextColumn::make('referencedSop.version')
                    ->label('Current SOP version')
                    ->sortable(),
                TextColumn::make('referenced_sop_version')
                    ->label('Linked version')
                    ->badge()
                    ->color(fn (ControlledDocument $record): string => $this->linkedVersionIsStale($record) ? 'warning' : 'gray'),
                TextColumn::make('document_number')->label('Document #')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->limit(40),
                TextColumn::make('documentType.name')->label('Type')->badge(),
                TextColumn::make('version')->label('Doc version'),
                TextColumn::make('documentStatus.name')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ControlledDocument $record): string => $record->displayStatusLabel())
                    ->color(fn (ControlledDocument $record): string => DocumentStatusColor::for($record->documentStatus?->code)),
                TextColumn::make('department.name')->label('Department')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('referenced_controlled_document_id')
                    ->label('Referenced SOP')
                    ->relationship(
                        'referencedSop',
                        'document_number',
                        fn (Builder $query): Builder => $query
                            ->whereHas('dependentDocuments')
                            ->orderBy('document_number'),
                    )
                    ->getOptionLabelFromRecordUsing(fn (ControlledDocument $record): string => "{$record->document_number} — {$record->title}")
                    ->searchable()
                    ->preload(),
                SelectFilter::make('document_type_id')->relationship('documentType', 'name')->label('Type'),
                SelectFilter::make('document_status_id')->relationship('documentStatus', 'name')->label('Status'),
                SelectFilter::make('department_id')->relationship('department', 'name')->label('Department'),
            ])
            ->defaultSort('referenced_sop_number')
            ->recordUrl(fn (ControlledDocument $record): string => ControlledDocumentResource::getUrl('view', ['record' => $record]))
            ->headerActions([$this->exportCsvAction()])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No SOP references')
            ->emptyStateDescription('No controlled documents currently reference an SOP.');
    }

    protected function exportFilename(): string
    {
        return 'sop-where-used-'.now()->format('Y-m-d-His');
    }

    protected function exportHeaders(): array
    {
        return [
            'Referenced SOP #',
            'SOP title',
            'Current SOP version',
            'Linked version',
            'Document #',
            'Title',
            'Type',
            'Doc version',
            'Status',
            'Department',
        ];
    }

    protected function exportRows(): iterable
    {
        foreach ($this->reportQuery()->lazyById() as $document) {
            yield [
                $document->referencedSop?->document_number ?? $document->referenced_sop_number,
                $document->referencedSop?->title,
                $document->referencedSop?->version,
                $document->referenced_sop_version,
                $document->document_number,
                $document->title,
                $document->documentType?->name,
                $document->version,
                $document->displayStatusLabel(),
                $document->department?->name,
            ];
        }
    }

    /**
     * @return Builder<ControlledDocument>
     */
    private function reportQuery(): Builder
    {
        return ControlledDocument::query()
            ->with([
                'referencedSop',
                'documentType',
                'documentStatus',
                'department',
                'approvals.workflowStep.approvalStepType',
                'approvals.workflowStep.role',
                'approvals.workflowStep.department',
                'approvals.approvalDecision',
            ])
            ->whereNotNull('referenced_controlled_document_id');
    }

    private function linkedVersionIsStale(ControlledDocument $document): bool
    {
        if ($document->referenced_sop_version === null || $document->referencedSop?->version === null) {
            return false;
        }

        return (int) $document->referenced_sop_version !== (int) $document->referencedSop->version;
    }
}
