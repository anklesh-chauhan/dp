<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula;

use App\Domain\QMS\Models\CompetencyCurriculum;
use App\Enums\ProductModule;
use App\Filament\Resources\CompetencyCurricula\Pages\CreateCompetencyCurriculum;
use App\Filament\Resources\CompetencyCurricula\Pages\EditCompetencyCurriculum;
use App\Filament\Resources\CompetencyCurricula\Pages\ListCompetencyCurricula;
use App\Filament\Resources\CompetencyCurricula\Pages\ViewCompetencyCurriculum;
use App\Filament\Resources\CompetencyCurricula\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\CompetencyCurricula\RelationManagers\UserCompetenciesRelationManager;
use App\Filament\Resources\CompetencyCurricula\Schemas\CompetencyCurriculumForm;
use App\Filament\Resources\CompetencyCurricula\Schemas\CompetencyCurriculumInfolist;
use App\Filament\Resources\CompetencyCurricula\Tables\CompetencyCurriculaTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class CompetencyCurriculumResource extends Resource
{
    protected static ?string $model = CompetencyCurriculum::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Competency curricula';

    protected static ?string $modelLabel = 'Competency curriculum';

    protected static ?string $pluralModelLabel = 'Competency curricula';

    protected static ?string $recordTitleAttribute = 'name';

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
        return CompetencyCurriculumForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CompetencyCurriculumInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompetencyCurriculaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            UserCompetenciesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompetencyCurricula::route('/'),
            'create' => CreateCompetencyCurriculum::route('/create'),
            'view' => ViewCompetencyCurriculum::route('/{record}'),
            'edit' => EditCompetencyCurriculum::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:CompetencyCurriculum');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:CompetencyCurriculum');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:CompetencyCurriculum');
    }

    public static function canEdit(mixed $record): bool
    {
        return (bool) auth()->user()?->can('Update:CompetencyCurriculum');
    }

    public static function canDelete(mixed $record): bool
    {
        return (bool) auth()->user()?->can('Manage:CompetencyCurriculum');
    }

    public static function canDeleteAny(): bool
    {
        return (bool) auth()->user()?->can('Manage:CompetencyCurriculum');
    }
}
