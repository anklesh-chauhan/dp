<?php

declare(strict_types=1);

namespace App\Filament\Resources\NumberSeries;

use App\Filament\Clusters\Settings\SettingsCluster;
use App\Filament\Resources\NumberSeries\Pages\EditNumberSeries;
use App\Filament\Resources\NumberSeries\Pages\ListNumberSeries;
use App\Filament\Resources\NumberSeries\Schemas\NumberSeriesForm;
use App\Filament\Resources\NumberSeries\Tables\NumberSeriesTable;
use App\Models\NumberSeries;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class NumberSeriesResource extends Resource
{
    protected static ?string $model = NumberSeries::class;

    protected static ?string $cluster = SettingsCluster::class;

    protected static string|UnitEnum|null $navigationGroup = 'DMS Configuration';

    protected static ?int $navigationSort = 1007;

    protected static ?string $navigationLabel = 'Number Series';

    protected static ?string $modelLabel = 'Number Series';

    protected static ?string $pluralModelLabel = 'Number Series';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Hashtag;

    public static function form(Schema $schema): Schema
    {
        return NumberSeriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NumberSeriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('documentType');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNumberSeries::route('/'),
            'edit' => EditNumberSeries::route('/{record}/edit'),
        ];
    }
}
