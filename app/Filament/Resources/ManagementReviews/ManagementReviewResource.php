<?php

declare(strict_types=1);

namespace App\Filament\Resources\ManagementReviews;

use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Models\ManagementReview;
use App\Enums\ProductModule;
use App\Filament\Resources\ManagementReviews\Pages\CreateManagementReview;
use App\Filament\Resources\ManagementReviews\Pages\EditManagementReview;
use App\Filament\Resources\ManagementReviews\Pages\ListManagementReviews;
use App\Filament\Resources\ManagementReviews\Pages\ViewManagementReview;
use App\Filament\Resources\ManagementReviews\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ManagementReviews\Schemas\ManagementReviewForm;
use App\Filament\Resources\ManagementReviews\Schemas\ManagementReviewInfolist;
use App\Filament\Resources\ManagementReviews\Tables\ManagementReviewsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ManagementReviewResource extends Resource
{
    protected static ?string $model = ManagementReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 9;

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
        return ManagementReviewForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ManagementReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManagementReviewsTable::configure($table);
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
            'index' => ListManagementReviews::route('/'),
            'create' => CreateManagementReview::route('/create'),
            'view' => ViewManagementReview::route('/{record}'),
            'edit' => EditManagementReview::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:ManagementReview');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:ManagementReview');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:ManagementReview');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ManagementReview
            && $record->status === ManagementReviewStatus::Draft
            && (bool) auth()->user()?->can('Update:ManagementReview');
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
