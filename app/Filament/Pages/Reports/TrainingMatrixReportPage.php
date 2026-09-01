<?php

declare(strict_types=1);

namespace App\Filament\Pages\Reports;

use App\Domain\TMS\Services\RoleTrainingMatrixService;
use App\Enums\ProductModule;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class TrainingMatrixReportPage extends OperationalReportPage
{
    protected static string|UnitEnum|null $navigationGroup = 'TMS · Reports';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Training matrix';

    protected static ?string $title = 'Training matrix';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'reports/training-matrix';

    protected static string|array $routeMiddleware = ['module:tms'];

    public static function productModule(): ProductModule
    {
        return ProductModule::TMS;
    }

    public static function reportPermission(): string
    {
        return 'View:TrainingMatrix';
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn () => app(RoleTrainingMatrixService::class)->rows())
            ->columns([
                TextColumn::make('designation_name')->label('Designation')->sortable(),
                TextColumn::make('user_name')->label('Person')->sortable(),
                TextColumn::make('program_code')->label('Program')->sortable(),
                TextColumn::make('document_number')->label('Document')->sortable(),
                TextColumn::make('document_title')->label('Title')->limit(40),
                TextColumn::make('assignment_status')->label('Status')->badge(),
            ])
            ->paginated([25, 50, 100])
            ->headerActions([
                $this->exportCsvAction(),
            ]);
    }

    protected function exportFilename(): string
    {
        return 'training-matrix.csv';
    }

    protected function exportHeaders(): array
    {
        return [
            'Designation Code',
            'Designation',
            'Program Code',
            'Program',
            'Document Number',
            'Document Title',
            'User',
            'Status',
        ];
    }

    protected function exportRows(): iterable
    {
        foreach (app(RoleTrainingMatrixService::class)->rows() as $row) {
            yield [
                $row['designation_code'],
                $row['designation_name'],
                $row['program_code'],
                $row['program_name'],
                $row['document_number'],
                $row['document_title'],
                $row['user_name'],
                $row['assignment_status'],
            ];
        }
    }
}
