<?php

declare(strict_types=1);

namespace App\Filament\Resources\Investigations;

use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Investigation;
use App\Enums\ProductModule;
use App\Filament\Resources\Investigations\Pages\CreateInvestigation;
use App\Filament\Resources\Investigations\Pages\EditInvestigation;
use App\Filament\Resources\Investigations\Pages\ListInvestigations;
use App\Filament\Resources\Investigations\Pages\ViewInvestigation;
use App\Filament\Resources\Investigations\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\Investigations\Schemas\InvestigationForm;
use App\Filament\Resources\Investigations\Schemas\InvestigationInfolist;
use App\Filament\Resources\Investigations\Tables\InvestigationsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class InvestigationResource extends Resource
{
    protected static ?string $model = Investigation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'investigation_number';

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
        return InvestigationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvestigationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvestigationsTable::configure($table);
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
            'index' => ListInvestigations::route('/'),
            'create' => CreateInvestigation::route('/create'),
            'view' => ViewInvestigation::route('/{record}'),
            'edit' => EditInvestigation::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:Investigation');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:Investigation');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:Investigation');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof Investigation
            && in_array($record->status, [InvestigationStatus::Draft, InvestigationStatus::InProgress], true)
            && (bool) auth()->user()?->can('Update:Investigation');
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
