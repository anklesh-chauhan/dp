<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets;

use App\Domain\QMS\Enums\EquipmentAssetStatus;
use App\Domain\QMS\Models\EquipmentAsset;
use App\Enums\ProductModule;
use App\Filament\Resources\EquipmentAssets\Pages\CreateEquipmentAsset;
use App\Filament\Resources\EquipmentAssets\Pages\EditEquipmentAsset;
use App\Filament\Resources\EquipmentAssets\Pages\ListEquipmentAssets;
use App\Filament\Resources\EquipmentAssets\Pages\ViewEquipmentAsset;
use App\Filament\Resources\EquipmentAssets\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\EquipmentAssets\RelationManagers\CalibrationsRelationManager;
use App\Filament\Resources\EquipmentAssets\RelationManagers\MaintenancesRelationManager;
use App\Filament\Resources\EquipmentAssets\RelationManagers\QualificationsRelationManager;
use App\Filament\Resources\EquipmentAssets\Schemas\EquipmentAssetForm;
use App\Filament\Resources\EquipmentAssets\Schemas\EquipmentAssetInfolist;
use App\Filament\Resources\EquipmentAssets\Tables\EquipmentAssetsTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class EquipmentAssetResource extends Resource
{
    protected static ?string $model = EquipmentAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 16;

    protected static ?string $navigationLabel = 'Equipment Assets';

    protected static ?string $modelLabel = 'Equipment Asset';

    protected static ?string $recordTitleAttribute = 'asset_number';

    protected static string|array $routeMiddleware = ['module:qms'];

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS) && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && parent::shouldRegisterNavigation();
    }

    public static function form(Schema $schema): Schema
    {
        return EquipmentAssetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipmentAssetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipmentAssetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QualificationsRelationManager::class,
            CalibrationsRelationManager::class,
            MaintenancesRelationManager::class,
            AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipmentAssets::route('/'),
            'create' => CreateEquipmentAsset::route('/create'),
            'view' => ViewEquipmentAsset::route('/{record}'),
            'edit' => EditEquipmentAsset::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:EquipmentAsset');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:EquipmentAsset');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:EquipmentAsset');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof EquipmentAsset
            && $record->status !== EquipmentAssetStatus::Decommissioned
            && (bool) auth()->user()?->can('Update:EquipmentAsset');
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
