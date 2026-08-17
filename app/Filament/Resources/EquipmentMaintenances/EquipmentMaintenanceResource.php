<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentMaintenances;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Domain\QMS\Models\EquipmentMaintenance;
use App\Enums\ProductModule;
use App\Filament\Resources\EquipmentMaintenances\Pages\CreateEquipmentMaintenance;
use App\Filament\Resources\EquipmentMaintenances\Pages\EditEquipmentMaintenance;
use App\Filament\Resources\EquipmentMaintenances\Pages\ListEquipmentMaintenances;
use App\Filament\Resources\EquipmentMaintenances\Pages\ViewEquipmentMaintenance;
use App\Filament\Resources\EquipmentMaintenances\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\EquipmentMaintenances\Schemas\EquipmentMaintenanceForm;
use App\Filament\Resources\EquipmentMaintenances\Schemas\EquipmentMaintenanceInfolist;
use App\Filament\Resources\EquipmentMaintenances\Tables\EquipmentMaintenancesTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class EquipmentMaintenanceResource extends Resource
{
    protected static ?string $model = EquipmentMaintenance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrench;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 23;

    protected static ?string $navigationLabel = 'Preventive Maintenance';

    protected static ?string $modelLabel = 'Equipment Maintenance';

    protected static ?string $recordTitleAttribute = 'work_order_number';

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
        return EquipmentMaintenanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipmentMaintenanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipmentMaintenancesTable::configure($table);
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
            'index' => ListEquipmentMaintenances::route('/'),
            'create' => CreateEquipmentMaintenance::route('/create'),
            'view' => ViewEquipmentMaintenance::route('/{record}'),
            'edit' => EditEquipmentMaintenance::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:EquipmentMaintenance');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:EquipmentMaintenance');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:EquipmentMaintenance');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof EquipmentMaintenance
            && $record->status === EquipmentMaintenanceStatus::Planned
            && (bool) auth()->user()?->can('Update:EquipmentMaintenance');
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
