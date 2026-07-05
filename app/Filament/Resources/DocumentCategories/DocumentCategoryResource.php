<?php

namespace App\Filament\Resources\DocumentCategories;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\DocumentCategories\Pages\ManageDocumentCategories;
use App\Models\DocumentCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DocumentCategoryResource extends Resource
{
    protected static ?string $model = DocumentCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'General Masters';

    protected static ?int $navigationSort = 1004;

    protected static ?string $recordTitleAttribute = 'DocumentCategory';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('DocumentCategory')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ManageDocumentCategories::route('/'),
        ];
    }
}
