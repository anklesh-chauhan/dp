<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserCompetencies;

use App\Domain\TMS\Models\UserCompetency;
use App\Enums\ProductModule;
use App\Filament\Resources\UserCompetencies\Pages\ListUserCompetencies;
use App\Filament\Resources\UserCompetencies\Pages\ViewUserCompetency;
use App\Filament\Resources\UserCompetencies\Schemas\UserCompetencyInfolist;
use App\Filament\Resources\UserCompetencies\Tables\UserCompetenciesTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class UserCompetencyResource extends Resource
{
    protected static ?string $model = UserCompetency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'TMS';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'User competencies';

    protected static ?string $modelLabel = 'User competency';

    protected static ?string $pluralModelLabel = 'User competencies';

    protected static ?string $recordTitleAttribute = 'id';

    protected static string|array $routeMiddleware = ['module:tms'];

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::TMS) && parent::canAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::TMS)
            && parent::shouldRegisterNavigation();
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserCompetencyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserCompetenciesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserCompetencies::route('/'),
            'view' => ViewUserCompetency::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:UserCompetency');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:UserCompetency');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
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
