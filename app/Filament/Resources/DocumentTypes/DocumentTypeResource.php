<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTypes;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\DocumentTypes\Pages\CreateDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\EditDocumentType;
use App\Filament\Resources\DocumentTypes\Pages\ListDocumentTypes;
use App\Filament\Resources\LookupResource;
use App\Models\DocumentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DocumentTypeResource extends LookupResource
{
    protected static ?string $model = DocumentType::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'DMS Configuration';

    protected static ?int $navigationSort = 1006;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->required()
                ->maxLength(50)
                ->unique(ignoreRecord: true),
            Toggle::make('requires_sop_reference'),
            Toggle::make('is_issuable'),
            Select::make('regulationTags')
                ->relationship('regulationTags', 'name')
                ->multiple()
                ->preload()
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('code')->searchable()->sortable(),
                IconColumn::make('requires_sop_reference')->boolean(),
                IconColumn::make('is_issuable')->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentTypes::route('/'),
            // 'create' => CreateDocumentType::route('/create'),
            // 'edit' => EditDocumentType::route('/{record}/edit'),
        ];
    }
}
