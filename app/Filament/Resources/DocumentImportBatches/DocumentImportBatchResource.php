<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentImportBatches;

use App\Filament\Resources\DocumentImportBatches\Pages\ListDocumentImportBatches;
use App\Filament\Resources\DocumentImportBatches\Pages\ViewDocumentImportBatch;
use App\Filament\Resources\DocumentImportBatches\RelationManagers\ImportItemsRelationManager;
use App\Models\DocumentImportBatch;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DocumentImportBatchResource extends Resource
{
    protected static ?string $model = DocumentImportBatch::class;

    protected static string|UnitEnum|null $navigationGroup = 'DMS';

    protected static ?string $navigationLabel = 'Import Batches';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Batch')->placeholder('Unnamed')->searchable(),
            TextColumn::make('source_type')->label('Source')->badge(),
            TextColumn::make('status')->badge(),
            TextColumn::make('total_items')->label('Files')->numeric(),
            TextColumn::make('successful_items')->label('Imported')->numeric(),
            TextColumn::make('failed_items')->label('Failed')->numeric(),
            TextColumn::make('creator.name')->label('Imported By')->placeholder('System'),
            TextColumn::make('created_at')->dateTime()->sortable(),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentImportBatches::route('/'),
            'view' => ViewDocumentImportBatch::route('/{record}'),
        ];
    }

    public static function getRelations(): array
    {
        return [ImportItemsRelationManager::class];
    }
}
