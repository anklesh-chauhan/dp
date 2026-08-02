<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Enums\ProductModule;
use App\Filament\Resources\ChangeControls\Pages\CreateChangeControl;
use App\Filament\Resources\ChangeControls\Pages\EditChangeControl;
use App\Filament\Resources\ChangeControls\Pages\ListChangeControls;
use App\Filament\Resources\ChangeControls\Pages\ViewChangeControl;
use App\Filament\Resources\ChangeControls\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ChangeControls\RelationManagers\DocumentImpactsRelationManager;
use App\Filament\Resources\ChangeControls\Schemas\ChangeControlForm;
use App\Filament\Resources\ChangeControls\Schemas\ChangeControlInfolist;
use App\Filament\Resources\ChangeControls\Tables\ChangeControlsTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ChangeControlResource extends Resource
{
    protected static ?string $model = ChangeControl::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'change_number';

    protected static string|array $routeMiddleware = ['module:qms'];

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && parent::shouldRegisterNavigation();
    }

    public static function form(Schema $schema): Schema
    {
        return ChangeControlForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChangeControlInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChangeControlsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DocumentImpactsRelationManager::class,
            AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChangeControls::route('/'),
            'create' => CreateChangeControl::route('/create'),
            'view' => ViewChangeControl::route('/{record}'),
            'edit' => EditChangeControl::route('/{record}/edit'),
        ];
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ChangeControl
            && $record->status === ChangeControlStatus::Draft
            && parent::canEdit($record);
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
