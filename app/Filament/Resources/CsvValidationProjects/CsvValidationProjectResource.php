<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects;

use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Enums\ProductModule;
use App\Filament\Resources\CsvValidationProjects\Pages\CreateCsvValidationProject;
use App\Filament\Resources\CsvValidationProjects\Pages\EditCsvValidationProject;
use App\Filament\Resources\CsvValidationProjects\Pages\ListCsvValidationProjects;
use App\Filament\Resources\CsvValidationProjects\Pages\ViewCsvValidationProject;
use App\Filament\Resources\CsvValidationProjects\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\CsvValidationProjects\RelationManagers\PeriodicReviewsRelationManager;
use App\Filament\Resources\CsvValidationProjects\RelationManagers\RequirementsRelationManager;
use App\Filament\Resources\CsvValidationProjects\RelationManagers\RisksRelationManager;
use App\Filament\Resources\CsvValidationProjects\RelationManagers\SpecificationsRelationManager;
use App\Filament\Resources\CsvValidationProjects\RelationManagers\TestCasesRelationManager;
use App\Filament\Resources\CsvValidationProjects\RelationManagers\TestExecutionsRelationManager;
use App\Filament\Resources\CsvValidationProjects\Schemas\CsvValidationProjectForm;
use App\Filament\Resources\CsvValidationProjects\Schemas\CsvValidationProjectInfolist;
use App\Filament\Resources\CsvValidationProjects\Tables\CsvValidationProjectsTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class CsvValidationProjectResource extends Resource
{
    protected static ?string $model = CsvValidationProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 1;

    protected static string|array $routeMiddleware = ['module:qms'];

    protected static ?string $recordTitleAttribute = 'project_number';

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
        return CsvValidationProjectForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CsvValidationProjectInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CsvValidationProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RequirementsRelationManager::class,
            RisksRelationManager::class,
            SpecificationsRelationManager::class,
            TestCasesRelationManager::class,
            TestExecutionsRelationManager::class,
            PeriodicReviewsRelationManager::class,
            AuditEventsRelationManager::class,
        ];
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof CsvValidationProject
            && ! in_array($record->status, [
                CsvValidationProjectStatus::Released,
                CsvValidationProjectStatus::Retired,
                CsvValidationProjectStatus::Cancelled,
            ], true)
            && parent::canEdit($record);
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCsvValidationProjects::route('/'),
            'create' => CreateCsvValidationProject::route('/create'),
            'view' => ViewCsvValidationProject::route('/{record}'),
            'edit' => EditCsvValidationProject::route('/{record}/edit'),
        ];
    }
}
