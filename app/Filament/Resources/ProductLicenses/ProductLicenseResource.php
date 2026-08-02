<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductLicenses;

use App\Filament\Resources\ProductLicenses\Pages\ListProductLicenses;
use App\Filament\Resources\ProductLicenses\Pages\ViewProductLicense;
use App\Filament\Resources\ProductLicenses\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ProductLicenses\Schemas\ProductLicenseInfolist;
use App\Filament\Resources\ProductLicenses\Tables\ProductLicensesTable;
use App\Models\ProductLicense;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ProductLicenseResource extends Resource
{
    protected static ?string $model = ProductLicense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Core · Identity & Access';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'license_key';

    protected static ?string $modelLabel = 'Product License';

    protected static ?string $pluralModelLabel = 'Product Licenses';

    public static function table(Table $table): Table
    {
        return ProductLicensesTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductLicenseInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductLicenses::route('/'),
            'view' => ViewProductLicense::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
