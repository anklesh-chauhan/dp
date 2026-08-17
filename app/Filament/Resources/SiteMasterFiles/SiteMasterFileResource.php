<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles;

use App\Domain\QMS\Enums\SiteMasterFileStatus;
use App\Domain\QMS\Models\SiteMasterFile;
use App\Enums\ProductModule;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Filament\Resources\SiteMasterFiles\Pages\CreateSiteMasterFile;
use App\Filament\Resources\SiteMasterFiles\Pages\EditSiteMasterFile;
use App\Filament\Resources\SiteMasterFiles\Pages\ListSiteMasterFiles;
use App\Filament\Resources\SiteMasterFiles\Pages\ViewSiteMasterFile;
use App\Filament\Resources\SiteMasterFiles\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\SiteMasterFiles\Schemas\SiteMasterFileForm;
use App\Filament\Resources\SiteMasterFiles\Schemas\SiteMasterFileInfolist;
use App\Filament\Resources\SiteMasterFiles\Tables\SiteMasterFilesTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class SiteMasterFileResource extends Resource
{
    protected static ?string $model = SiteMasterFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 19;

    protected static ?string $navigationLabel = 'Site Master Files';

    protected static ?string $modelLabel = 'Site Master File';

    protected static ?string $recordTitleAttribute = 'smf_number';

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
        return SiteMasterFileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SiteMasterFileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiteMasterFilesTable::configure($table);
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
            'index' => ListSiteMasterFiles::route('/'),
            'create' => CreateSiteMasterFile::route('/create'),
            'view' => ViewSiteMasterFile::route('/{record}'),
            'edit' => EditSiteMasterFile::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:SiteMasterFile');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:SiteMasterFile');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:SiteMasterFile');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof SiteMasterFile
            && in_array($record->status, [
                SiteMasterFileStatus::Draft,
                SiteMasterFileStatus::InReview,
            ], true)
            && (bool) auth()->user()?->can('Update:SiteMasterFile');
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
