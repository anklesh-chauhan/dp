<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Enums\ProductModule;
use App\Filament\Resources\ControlledDocuments\ControlledDocumentResource;
use App\Models\DocumentIssuance;
use App\Models\IssuanceStatus;
use App\Support\Formatting\DateFormatSettings;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IssuanceRegisterReportPage extends OperationalReportPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $navigationLabel = 'Issuance Register';

    protected static ?string $title = 'Issuance Register';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'dms-reports/issuance-register';

    public static function productModule(): ProductModule
    {
        return ProductModule::DMS;
    }

    public static function reportPermission(): string
    {
        return 'ViewAny:DocumentIssuance';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->reportQuery())
            ->columns([
                TextColumn::make('issuance_number')->searchable()->sortable(),
                TextColumn::make('issuance_type')->label('Copy type')->badge(),
                TextColumn::make('document.document_number')->label('Document #')->searchable(),
                TextColumn::make('document.title')->label('Title')->limit(30),
                TextColumn::make('copy_number')->label('Copy #'),
                TextColumn::make('issuedToUser.name')->label('Issued to')->placeholder('—'),
                TextColumn::make('issuedToDepartment.name')->label('Department')->placeholder('—'),
                TextColumn::make('issuer.name')->label('Issued by'),
                TextColumn::make('issued_at')->dateTime()->sortable(),
                TextColumn::make('issuanceStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (DocumentIssuance $record): string => match ($record->issuanceStatus?->code) {
                        IssuanceStatus::ACTIVE => 'success',
                        IssuanceStatus::RECALLED => 'warning',
                        IssuanceStatus::DESTROYED => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('issuance_status_id')->relationship('issuanceStatus', 'name')->label('Status'),
                SelectFilter::make('issuance_type')->options([
                    DocumentIssuance::TYPE_REFERENCE => 'Reference copy',
                    DocumentIssuance::TYPE_EXECUTION => 'Writable execution record',
                ]),
            ])
            ->defaultSort('issued_at', 'desc')
            ->recordUrl(fn (DocumentIssuance $record): string => ControlledDocumentResource::getUrl('view', ['record' => $record->document_id]))
            ->headerActions([$this->exportCsvAction()])
            ->paginated([25, 50, 100])
            ->emptyStateHeading('No controlled copies')
            ->emptyStateDescription('No issuances match the current filters.');
    }

    protected function exportFilename(): string
    {
        return 'issuance-register-'.now()->format('Y-m-d-His');
    }

    protected function exportHeaders(): array
    {
        return ['Issuance #', 'Copy type', 'Document #', 'Title', 'Copy #', 'Issued to', 'Department', 'Issued by', 'Issued at', 'Status'];
    }

    protected function exportRows(): iterable
    {
        $dates = app(DateFormatSettings::class);

        foreach ($this->reportQuery()->lazyById() as $issuance) {
            yield [
                $issuance->issuance_number,
                $issuance->issuance_type,
                $issuance->document?->document_number,
                $issuance->document?->title,
                $issuance->copy_number,
                $issuance->issuedToUser?->name,
                $issuance->issuedToDepartment?->name,
                $issuance->issuer?->name,
                $dates->formatDateTime($issuance->issued_at),
                $issuance->issuanceStatus?->name,
            ];
        }
    }

    /**
     * @return Builder<DocumentIssuance>
     */
    private function reportQuery(): Builder
    {
        return DocumentIssuance::query()->with([
            'document',
            'issuedToUser',
            'issuedToDepartment',
            'issuer',
            'issuanceStatus',
        ]);
    }
}
