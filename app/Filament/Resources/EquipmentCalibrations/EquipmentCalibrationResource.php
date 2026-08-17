<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentCalibrations;

use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Domain\QMS\Models\EquipmentCalibration;
use App\Enums\ProductModule;
use App\Filament\Resources\EquipmentCalibrations\Pages\CreateEquipmentCalibration;
use App\Filament\Resources\EquipmentCalibrations\Pages\EditEquipmentCalibration;
use App\Filament\Resources\EquipmentCalibrations\Pages\ListEquipmentCalibrations;
use App\Filament\Resources\EquipmentCalibrations\Pages\ViewEquipmentCalibration;
use App\Filament\Resources\EquipmentCalibrations\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\EquipmentCalibrations\Schemas\EquipmentCalibrationForm;
use App\Filament\Resources\EquipmentCalibrations\Schemas\EquipmentCalibrationInfolist;
use App\Filament\Resources\EquipmentCalibrations\Tables\EquipmentCalibrationsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class EquipmentCalibrationResource extends Resource
{
    protected static ?string $model = EquipmentCalibration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 22;

    protected static ?string $navigationLabel = 'Calibrations';

    protected static ?string $modelLabel = 'Equipment Calibration';

    protected static ?string $recordTitleAttribute = 'calibration_number';

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
        return EquipmentCalibrationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipmentCalibrationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipmentCalibrationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QualityAttachmentsRelationManager::class,
            AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEquipmentCalibrations::route('/'),
            'create' => CreateEquipmentCalibration::route('/create'),
            'view' => ViewEquipmentCalibration::route('/{record}'),
            'edit' => EditEquipmentCalibration::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:EquipmentCalibration');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:EquipmentCalibration');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:EquipmentCalibration');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof EquipmentCalibration
            && $record->status === EquipmentCalibrationStatus::Scheduled
            && (bool) auth()->user()?->can('Update:EquipmentCalibration');
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
