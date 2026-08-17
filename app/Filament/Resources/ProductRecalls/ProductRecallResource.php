<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductRecalls;

use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Domain\QMS\Models\ProductRecall;
use App\Enums\ProductModule;
use App\Filament\Resources\ProductRecalls\Pages\CreateProductRecall;
use App\Filament\Resources\ProductRecalls\Pages\EditProductRecall;
use App\Filament\Resources\ProductRecalls\Pages\ListProductRecalls;
use App\Filament\Resources\ProductRecalls\Pages\ViewProductRecall;
use App\Filament\Resources\ProductRecalls\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ProductRecalls\Schemas\ProductRecallForm;
use App\Filament\Resources\ProductRecalls\Schemas\ProductRecallInfolist;
use App\Filament\Resources\ProductRecalls\Tables\ProductRecallsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ProductRecallResource extends Resource
{
    protected static ?string $model = ProductRecall::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'recall_number';

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
        return ProductRecallForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductRecallInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductRecallsTable::configure($table);
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
            'index' => ListProductRecalls::route('/'),
            'create' => CreateProductRecall::route('/create'),
            'view' => ViewProductRecall::route('/{record}'),
            'edit' => EditProductRecall::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:ProductRecall');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:ProductRecall');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:ProductRecall');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ProductRecall
            && $record->status === ProductRecallStatus::Draft
            && (bool) auth()->user()?->can('Update:ProductRecall');
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
