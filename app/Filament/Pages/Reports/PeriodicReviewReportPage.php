<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\ProductModule;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\ControlledDocument;
use App\Models\DocumentStatus;
use App\Support\Formatting\DateFormatSettings;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PeriodicReviewReportPage extends OperationalReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Periodic Review';

    protected static ?string $title = 'Periodic Review';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'dms-reports/periodic-review';

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
                TextColumn::make('effective_date')->date()->sortable(),
                TextColumn::make('review_date')->date()->sortable(),
                TextColumn::make('review_state')
                    ->label('Review state')
                    ->badge()
                    ->state(fn (ControlledDocument $record): string => $this->reviewState($record))
                    ->color(fn (ControlledDocument $record): string => $this->reviewState($record) === 'Overdue' ? 'danger' : 'warning'),
            ])
            ->filters([
                SelectFilter::make('review_window')
                    ->label('Window')
                    ->options([
                        'overdue' => 'Overdue',
                        'due_soon' => 'Due in 30 days',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'overdue' => $query->whereDate('review_date', '<', now()->toDateString()),
                            'due_soon' => $query->whereDate('review_date', '>=', now()->toDateString())
                                ->whereDate('review_date', '<=', now()->addDays(30)->toDateString()),
                            default => $query,
                        };
                    }),
                SelectFilter::make('department_id')->relationship('department', 'name')->label('Department'),
            ])
            ->defaultSort('review_date')
            ->recordUrl(fn (ControlledDocument $record): string => ControlledDocumentResource::getUrl('view', ['record' => $record]))
            ->headerActions([$this->exportCsvAction()])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No reviews due')
            ->emptyStateDescription('No effective documents are overdue or due for review in the next 30 days.');
    }

    protected function exportFilename(): string
    {
        return 'periodic-review-'.now()->format('Y-m-d-His');
    }

    protected function exportHeaders(): array
    {
        return ['Document #', 'Title', 'Version', 'Department', 'Owner', 'Effective Date', 'Review Date', 'Review State'];
    }

    protected function exportRows(): iterable
    {
        $dates = app(DateFormatSettings::class);

        foreach ($this->reportQuery()->lazyById() as $document) {
            yield [
                $document->document_number,
                $document->title,
                $document->version,
                $document->department?->name,
                $document->owner?->name,
                $dates->formatDate($document->effective_date),
                $dates->formatDate($document->review_date),
                $this->reviewState($document),
            ];
        }
    }

    /**
     * @return Builder<ControlledDocument>
     */
    private function reportQuery(): Builder
    {
        return ControlledDocument::query()
            ->with(['department', 'owner', 'documentStatus'])
            ->where('document_status_id', DocumentStatus::idFor(DocumentStatus::EFFECTIVE))
            ->whereNotNull('review_date')
            ->whereDate('review_date', '<=', now()->addDays(30)->toDateString());
    }

    private function reviewState(ControlledDocument $document): string
    {
        if ($document->review_date === null) {
            return '—';
        }

        return $document->review_date->toDateString() < now()->toDateString()
            ? 'Overdue'
            : 'Due soon';
    }
}
