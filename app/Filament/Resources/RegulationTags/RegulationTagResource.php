<?php

namespace App\Filament\Resources\RegulationTags;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\RegulationTags\Pages\ManageRegulationTags;
use App\Models\DocumentType;
use App\Models\RegulationTag;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class RegulationTagResource extends Resource
{
    protected static ?string $model = RegulationTag::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'System & Security Configuration';

    protected static ?int $navigationSort = 2009;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255),
                TextInput::make('description')
                    ->maxLength(255),
                ColorPicker::make('color'),
                TextInput::make('icon')
                    ->maxLength(255),
                Select::make('documentTypes')
                    ->label('Default Document Types')
                    ->relationship('documentTypes', 'name')
                    ->multiple()
                    ->preload()
                    ->helperText('Optional: preselect this tag when one of these document types is chosen.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')->searchable()->sortable(),
            ])
            ->filters([
                SelectFilter::make('document_types')
                    ->options(DocumentType::query()->pluck('name', 'id'))
                    ->multiple(),
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
            'index' => ManageRegulationTags::route('/'),
        ];
    }
}
