<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentQualifications;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Models\EquipmentQualification;
use App\Enums\ProductModule;
use App\Filament\Resources\EquipmentQualifications\Pages\CreateEquipmentQualification;
use App\Filament\Resources\EquipmentQualifications\Pages\EditEquipmentQualification;
use App\Filament\Resources\EquipmentQualifications\Pages\ListEquipmentQualifications;
use App\Filament\Resources\EquipmentQualifications\Pages\ViewEquipmentQualification;
use App\Filament\Resources\EquipmentQualifications\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\EquipmentQualifications\Schemas\EquipmentQualificationForm;
use App\Filament\Resources\EquipmentQualifications\Schemas\EquipmentQualificationInfolist;
use App\Filament\Resources\EquipmentQualifications\Tables\EquipmentQualificationsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class EquipmentQualificationResource extends Resource
{
    protected static ?string $model = EquipmentQualification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 17;

    protected static ?string $navigationLabel = 'Equipment Qualifications';

    protected static ?string $modelLabel = 'Equipment Qualification';

    protected static ?string $recordTitleAttribute = 'qualification_number';

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
        return EquipmentQualificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EquipmentQualificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EquipmentQualificationsTable::configure($table);
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
            'index' => ListEquipmentQualifications::route('/'),
            'create' => CreateEquipmentQualification::route('/create'),
            'view' => ViewEquipmentQualification::route('/{record}'),
            'edit' => EditEquipmentQualification::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:EquipmentQualification');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:EquipmentQualification');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:EquipmentQualification');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof EquipmentQualification
            && $record->status === EquipmentQualificationStatus::Draft
            && (bool) auth()->user()?->can('Update:EquipmentQualification');
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
