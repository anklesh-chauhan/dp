<?php

declare(strict_types=1);

namespace App\Filament\Resources\Deviations;

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Enums\ProductModule;
use App\Filament\Resources\Deviations\Pages\CreateDeviation;
use App\Filament\Resources\Deviations\Pages\EditDeviation;
use App\Filament\Resources\Deviations\Pages\ListDeviations;
use App\Filament\Resources\Deviations\Pages\ViewDeviation;
use App\Filament\Resources\Deviations\RelationManagers\ApprovalInstancesRelationManager;
use App\Filament\Resources\Deviations\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\Deviations\Schemas\DeviationForm;
use App\Filament\Resources\Deviations\Schemas\DeviationInfolist;
use App\Filament\Resources\Deviations\Tables\DeviationsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class DeviationResource extends Resource
{
    protected static ?string $model = Deviation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'QMS · Quality Events';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'deviation_number';

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
        return DeviationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DeviationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DeviationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QualityAttachmentsRelationManager::class,
            ApprovalInstancesRelationManager::class,
            AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeviations::route('/'),
            'create' => CreateDeviation::route('/create'),
            'view' => ViewDeviation::route('/{record}'),
            'edit' => EditDeviation::route('/{record}/edit'),
        ];
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:Deviation');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:Deviation');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:Deviation');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof Deviation
            && $record->status === DeviationStatus::Draft
            && (bool) auth()->user()?->can('Update:Deviation');
    }
}
