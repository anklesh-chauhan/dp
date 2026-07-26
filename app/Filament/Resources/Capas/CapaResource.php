<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas;

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Models\Capa;
use App\Enums\ProductModule;
use App\Filament\Resources\Capas\Pages\CreateCapa;
use App\Filament\Resources\Capas\Pages\EditCapa;
use App\Filament\Resources\Capas\Pages\ListCapas;
use App\Filament\Resources\Capas\Pages\ViewCapa;
use App\Filament\Resources\Capas\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\Capas\Schemas\CapaForm;
use App\Filament\Resources\Capas\Schemas\CapaInfolist;
use App\Filament\Resources\Capas\Tables\CapasTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class CapaResource extends Resource
{
    protected static ?string $model = Capa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'QMS · Quality Events';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'capa_number';

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
        return CapaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CapaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CapasTable::configure($table);
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
            'index' => ListCapas::route('/'),
            'create' => CreateCapa::route('/create'),
            'view' => ViewCapa::route('/{record}'),
            'edit' => EditCapa::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:Capa');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:Capa');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:Capa');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof Capa
            && in_array($record->status, [CapaStatus::Draft, CapaStatus::Planned, CapaStatus::InProgress], true)
            && (bool) auth()->user()?->can('Update:Capa');
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
