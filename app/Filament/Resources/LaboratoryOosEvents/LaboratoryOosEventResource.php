<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOosEvents;

use App\Domain\QMS\Enums\LaboratoryOosStatus;
use App\Domain\QMS\Models\LaboratoryOosEvent;
use App\Enums\ProductModule;
use App\Filament\Resources\LaboratoryOosEvents\Pages\CreateLaboratoryOosEvent;
use App\Filament\Resources\LaboratoryOosEvents\Pages\EditLaboratoryOosEvent;
use App\Filament\Resources\LaboratoryOosEvents\Pages\ListLaboratoryOosEvents;
use App\Filament\Resources\LaboratoryOosEvents\Pages\ViewLaboratoryOosEvent;
use App\Filament\Resources\LaboratoryOosEvents\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\LaboratoryOosEvents\Schemas\LaboratoryOosEventForm;
use App\Filament\Resources\LaboratoryOosEvents\Schemas\LaboratoryOosEventInfolist;
use App\Filament\Resources\LaboratoryOosEvents\Tables\LaboratoryOosEventsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class LaboratoryOosEventResource extends Resource
{
    protected static ?string $model = LaboratoryOosEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 14;

    protected static ?string $recordTitleAttribute = 'event_number';

    protected static ?string $navigationLabel = 'Laboratory OOS/OOT';

    protected static ?string $modelLabel = 'Laboratory OOS/OOT Event';

    protected static ?string $pluralModelLabel = 'Laboratory OOS/OOT Events';

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
        return LaboratoryOosEventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LaboratoryOosEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaboratoryOosEventsTable::configure($table);
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
            'index' => ListLaboratoryOosEvents::route('/'),
            'create' => CreateLaboratoryOosEvent::route('/create'),
            'view' => ViewLaboratoryOosEvent::route('/{record}'),
            'edit' => EditLaboratoryOosEvent::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:LaboratoryOosEvent');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:LaboratoryOosEvent');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:LaboratoryOosEvent');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof LaboratoryOosEvent
            && in_array($record->status, [
                LaboratoryOosStatus::Draft,
                LaboratoryOosStatus::PhaseOne,
                LaboratoryOosStatus::PhaseTwo,
                LaboratoryOosStatus::InvalidationProposed,
            ], true)
            && (bool) auth()->user()?->can('Update:LaboratoryOosEvent');
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
