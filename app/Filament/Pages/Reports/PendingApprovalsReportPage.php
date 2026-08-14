<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\ProductModule;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Filament\Support\DocumentStatusColor;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PendingApprovalsReportPage extends OperationalReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Pending Approvals';

    protected static ?string $title = 'Pending Approvals';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'dms-reports/pending-approvals';

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
                TextColumn::make('version'),
                TextColumn::make('department.name')->label('Department'),
                TextColumn::make('owner.name')->label('Owner'),
                TextColumn::make('documentStatus.name')
                    ->label('Waiting at')
                    ->badge()
                    ->state(fn (ControlledDocument $record): string => $record->displayStatusLabel())
                    ->color(fn (ControlledDocument $record): string => DocumentStatusColor::for($record->documentStatus?->code)),
                TextColumn::make('updated_at')->label('Last updated')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')->relationship('department', 'name')->label('Department'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(fn (ControlledDocument $record): string => ControlledDocumentResource::getUrl('view', ['record' => $record]))
            ->headerActions([$this->exportCsvAction()])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No pending approvals')
            ->emptyStateDescription('No controlled documents are currently under review.');
    }

    protected function exportFilename(): string
    {
        return 'pending-approvals-'.now()->format('Y-m-d-His');
    }

    protected function exportHeaders(): array
    {
        return ['Document #', 'Title', 'Version', 'Department', 'Owner', 'Waiting at', 'Last updated'];
    }

    protected function exportRows(): iterable
    {
        foreach ($this->reportQuery()->lazyById() as $document) {
            yield [
                $document->document_number,
                $document->title,
                $document->version,
                $document->department?->name,
                $document->owner?->name,
                $document->displayStatusLabel(),
                $document->updated_at?->toDateTimeString(),
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
                'department',
                'owner',
                'documentStatus',
                'approvals.workflowStep.approvalStepType',
                'approvals.workflowStep.role',
                'approvals.workflowStep.department',
                'approvals.approvalDecision',
            ])
            ->where('document_status_id', DocumentStatus::idFor(DocumentStatus::UNDER_REVIEW));
    }
}
