<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductQualityReviews;

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Enums\ProductModule;
use App\Filament\Resources\ProductQualityReviews\Pages\CreateProductQualityReview;
use App\Filament\Resources\ProductQualityReviews\Pages\EditProductQualityReview;
use App\Filament\Resources\ProductQualityReviews\Pages\ListProductQualityReviews;
use App\Filament\Resources\ProductQualityReviews\Pages\ViewProductQualityReview;
use App\Filament\Resources\ProductQualityReviews\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ProductQualityReviews\Schemas\ProductQualityReviewForm;
use App\Filament\Resources\ProductQualityReviews\Schemas\ProductQualityReviewInfolist;
use App\Filament\Resources\ProductQualityReviews\Tables\ProductQualityReviewsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ProductQualityReviewResource extends Resource
{
    protected static ?string $model = ProductQualityReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'review_number';

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
        return ProductQualityReviewForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProductQualityReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductQualityReviewsTable::configure($table);
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
            'index' => ListProductQualityReviews::route('/'),
            'create' => CreateProductQualityReview::route('/create'),
            'view' => ViewProductQualityReview::route('/{record}'),
            'edit' => EditProductQualityReview::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:ProductQualityReview');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:ProductQualityReview');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:ProductQualityReview');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ProductQualityReview
            && in_array($record->status, [
                ProductQualityReviewStatus::Draft,
                ProductQualityReviewStatus::InProgress,
            ], true)
            && (bool) auth()->user()?->can('Update:ProductQualityReview');
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
