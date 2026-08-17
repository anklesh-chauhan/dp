<?php

declare(strict_types=1);

namespace App\Filament\Resources\ValidationMasterPlans;

use App\Domain\QMS\Enums\ValidationMasterPlanStatus;
use App\Domain\QMS\Models\ValidationMasterPlan;
use App\Enums\ProductModule;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Filament\Resources\ValidationMasterPlans\Pages\CreateValidationMasterPlan;
use App\Filament\Resources\ValidationMasterPlans\Pages\EditValidationMasterPlan;
use App\Filament\Resources\ValidationMasterPlans\Pages\ListValidationMasterPlans;
use App\Filament\Resources\ValidationMasterPlans\Pages\ViewValidationMasterPlan;
use App\Filament\Resources\ValidationMasterPlans\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ValidationMasterPlans\Schemas\ValidationMasterPlanForm;
use App\Filament\Resources\ValidationMasterPlans\Schemas\ValidationMasterPlanInfolist;
use App\Filament\Resources\ValidationMasterPlans\Tables\ValidationMasterPlansTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ValidationMasterPlanResource extends Resource
{
    protected static ?string $model = ValidationMasterPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationLabel = 'Validation Master Plans';

    protected static ?string $modelLabel = 'Validation Master Plan';

    protected static ?string $recordTitleAttribute = 'vmp_number';

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
        return ValidationMasterPlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ValidationMasterPlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ValidationMasterPlansTable::configure($table);
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
            'index' => ListValidationMasterPlans::route('/'),
            'create' => CreateValidationMasterPlan::route('/create'),
            'view' => ViewValidationMasterPlan::route('/{record}'),
            'edit' => EditValidationMasterPlan::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:ValidationMasterPlan');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:ValidationMasterPlan');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:ValidationMasterPlan');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ValidationMasterPlan
            && in_array($record->status, [
                ValidationMasterPlanStatus::Draft,
                ValidationMasterPlanStatus::UnderRevision,
            ], true)
            && (bool) auth()->user()?->can('Update:ValidationMasterPlan');
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
