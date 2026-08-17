<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductReturns;

use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Domain\QMS\Models\ProductReturn;
use App\Enums\ProductModule;
use App\Filament\Resources\ProductReturns\Pages\CreateProductReturn;
use App\Filament\Resources\ProductReturns\Pages\EditProductReturn;
use App\Filament\Resources\ProductReturns\Pages\ListProductReturns;
use App\Filament\Resources\ProductReturns\Pages\ViewProductReturn;
use App\Filament\Resources\ProductReturns\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ProductReturns\Schemas\ProductReturnForm;
use App\Filament\Resources\ProductReturns\Schemas\ProductReturnInfolist;
use App\Filament\Resources\ProductReturns\Tables\ProductReturnsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ProductReturnResource extends Resource
{
    protected static ?string $model = ProductReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 13;

    protected static ?string $recordTitleAttribute = 'return_number';

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
        return ProductReturnForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductReturnInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductReturnsTable::configure($table);
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
            'index' => ListProductReturns::route('/'),
            'create' => CreateProductReturn::route('/create'),
            'view' => ViewProductReturn::route('/{record}'),
            'edit' => EditProductReturn::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:ProductReturn');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:ProductReturn');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:ProductReturn');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ProductReturn
            && $record->status === ProductReturnStatus::Draft
            && (bool) auth()->user()?->can('Update:ProductReturn');
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
