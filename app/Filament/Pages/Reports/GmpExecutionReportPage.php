<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\ProductModule;
use App\Filament\Resources\DocumentExecutions\DocumentExecutionResource;
use App\Models\DocumentExecution;
use App\Models\DocumentType;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GmpExecutionReportPage extends OperationalReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'GMP Executions';

    protected static ?string $title = 'GMP Executions';

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'dms-reports/gmp-executions';

    public static function productModule(): ProductModule
    {
        return ProductModule::DMS;
    }

    public static function reportPermission(): string
    {
        return 'ViewAny:DocumentExecution';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->columns([
                TextColumn::make('execution_number')->searchable()->sortable(),
                TextColumn::make('document_number')->label('Master')->searchable(),
                TextColumn::make('document_version')->label('Version'),
                TextColumn::make('document_type_code')->label('Type')->badge(),
                TextColumn::make('batch_number')->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('disposition')->badge(),
                TextColumn::make('completedBy.name')->label('Executor')->placeholder('—'),
                TextColumn::make('supervisor.name')->label('Supervisor')->placeholder('—'),
                TextColumn::make('qaApprovedBy.name')->label('QA reviewer')->placeholder('—'),
                TextColumn::make('completed_at')->dateTime()->toggleable(),
                TextColumn::make('closed_at')->dateTime()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    DocumentExecution::STATUS_ISSUED => 'Issued',
                    DocumentExecution::STATUS_IN_PROGRESS => 'In progress',
                    DocumentExecution::STATUS_COMPLETED => 'Completed',
                    DocumentExecution::STATUS_UNDER_REVIEW => 'Under review',
                    DocumentExecution::STATUS_QA_REVIEW => 'QA review',
                    DocumentExecution::STATUS_CLOSED => 'Closed',
                ]),
                SelectFilter::make('document_type_code')->options([
                    DocumentType::FORM => 'Form',
                    DocumentType::LOG => 'Log',
                    DocumentType::CHECKLIST => 'Checklist',
                    DocumentType::BATCH_RECORD => 'BMR',
                    DocumentType::BATCH_PACKAGING_RECORD => 'BPR',
                ]),
                SelectFilter::make('disposition')->options([
                    DocumentExecution::DISPOSITION_PENDING => 'Pending',
                    DocumentExecution::DISPOSITION_RELEASED => 'Released',
                    DocumentExecution::DISPOSITION_REJECTED => 'Rejected',
                    DocumentExecution::DISPOSITION_NOT_APPLICABLE => 'Not applicable',
                ]),
            ])
            ->defaultSort('execution_number', 'desc')
            ->recordUrl(fn (DocumentExecution $record): string => DocumentExecutionResource::getUrl('view', ['record' => $record]))
            ->headerActions([$this->exportCsvAction()])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No execution records')
            ->emptyStateDescription('No GMP execution records match the current filters.');
    }

    protected function exportFilename(): string
    {
        return 'gmp-executions-'.now()->format('Y-m-d-His');
    }

    protected function exportHeaders(): array
    {
        return ['Execution #', 'Master', 'Version', 'Type', 'Batch', 'Status', 'Disposition', 'Executor', 'Supervisor', 'QA reviewer'];
    }

    protected function exportRows(): iterable
    {
        foreach ($this->reportQuery()->lazyById() as $execution) {
            yield [
                $execution->execution_number,
                $execution->document_number,
                $execution->document_version,
                $execution->document_type_code,
                $execution->batch_number,
                $execution->status,
                $execution->disposition,
                $execution->completedBy?->name,
                $execution->supervisor?->name,
                $execution->qaApprovedBy?->name,
            ];
        }
    }

    /**
     * @return Builder<DocumentExecution>
     */
    private function reportQuery(): Builder
    {
        return DocumentExecution::query()->with(['completedBy', 'supervisor', 'qaApprovedBy']);
    }
}
