<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierQualifications;

use App\Domain\QMS\Enums\SupplierQualificationStatus;
use App\Domain\QMS\Models\SupplierQualification;
use App\Enums\ProductModule;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Filament\Resources\SupplierQualifications\Pages\CreateSupplierQualification;
use App\Filament\Resources\SupplierQualifications\Pages\EditSupplierQualification;
use App\Filament\Resources\SupplierQualifications\Pages\ListSupplierQualifications;
use App\Filament\Resources\SupplierQualifications\Pages\ViewSupplierQualification;
use App\Filament\Resources\SupplierQualifications\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\SupplierQualifications\Schemas\SupplierQualificationForm;
use App\Filament\Resources\SupplierQualifications\Schemas\SupplierQualificationInfolist;
use App\Filament\Resources\SupplierQualifications\Tables\SupplierQualificationsTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class SupplierQualificationResource extends Resource
{
    protected static ?string $model = SupplierQualification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'supplier_number';

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
        return SupplierQualificationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierQualificationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierQualificationsTable::configure($table);
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
            'index' => ListSupplierQualifications::route('/'),
            'create' => CreateSupplierQualification::route('/create'),
            'view' => ViewSupplierQualification::route('/{record}'),
            'edit' => EditSupplierQualification::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:SupplierQualification');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:SupplierQualification');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:SupplierQualification');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof SupplierQualification
            && $record->status === SupplierQualificationStatus::Draft
            && (bool) auth()->user()?->can('Update:SupplierQualification');
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
