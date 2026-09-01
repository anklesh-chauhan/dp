<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingPrograms;

use App\Domain\TMS\Models\TrainingProgram;
use App\Enums\ProductModule;
use App\Filament\Resources\TrainingPrograms\Pages\CreateTrainingProgram;
use App\Filament\Resources\TrainingPrograms\Pages\EditTrainingProgram;
use App\Filament\Resources\TrainingPrograms\Pages\ListTrainingPrograms;
use App\Filament\Resources\TrainingPrograms\Pages\ViewTrainingProgram;
use App\Filament\Resources\TrainingPrograms\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\TrainingPrograms\RelationManagers\RoleRequirementsRelationManager;
use App\Filament\Resources\TrainingPrograms\Schemas\TrainingProgramForm;
use App\Filament\Resources\TrainingPrograms\Tables\TrainingProgramsTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class TrainingProgramResource extends Resource
{
    protected static ?string $model = TrainingProgram::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'TMS';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Training programs';

    protected static ?string $recordTitleAttribute = 'name';

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

    public static function form(Schema $schema): Schema
    {
        return TrainingProgramForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TrainingProgramsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            RoleRequirementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingPrograms::route('/'),
            'create' => CreateTrainingProgram::route('/create'),
            'view' => ViewTrainingProgram::route('/{record}'),
            'edit' => EditTrainingProgram::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:TrainingProgram');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:TrainingProgram');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:TrainingProgram');
    }

    public static function canEdit(mixed $record): bool
    {
        return (bool) auth()->user()?->can('Update:TrainingProgram');
    }

    public static function canDelete(mixed $record): bool
    {
        return (bool) auth()->user()?->can('Manage:TrainingProgram');
    }

    public static function canDeleteAny(): bool
    {
        return (bool) auth()->user()?->can('Manage:TrainingProgram');
    }
}
