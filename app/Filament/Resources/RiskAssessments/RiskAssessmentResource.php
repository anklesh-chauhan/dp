<?php

declare(strict_types=1);

namespace App\Filament\Resources\RiskAssessments;

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Models\RiskAssessment;
use App\Enums\ProductModule;
use App\Filament\Resources\RiskAssessments\Pages\CreateRiskAssessment;
use App\Filament\Resources\RiskAssessments\Pages\EditRiskAssessment;
use App\Filament\Resources\RiskAssessments\Pages\ListRiskAssessments;
use App\Filament\Resources\RiskAssessments\Pages\ViewRiskAssessment;
use App\Filament\Resources\RiskAssessments\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\RiskAssessments\RelationManagers\EventLinksRelationManager;
use App\Filament\Resources\RiskAssessments\Schemas\RiskAssessmentForm;
use App\Filament\Resources\RiskAssessments\Schemas\RiskAssessmentInfolist;
use App\Filament\Resources\RiskAssessments\Tables\RiskAssessmentsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class RiskAssessmentResource extends Resource
{
    protected static ?string $model = RiskAssessment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'risk_number';

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
        return RiskAssessmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RiskAssessmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RiskAssessmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            EventLinksRelationManager::class,
            QualityAttachmentsRelationManager::class,
            AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiskAssessments::route('/'),
            'create' => CreateRiskAssessment::route('/create'),
            'view' => ViewRiskAssessment::route('/{record}'),
            'edit' => EditRiskAssessment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:RiskAssessment');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:RiskAssessment');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:RiskAssessment');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof RiskAssessment
            && $record->status === RiskAssessmentStatus::Draft
            && (bool) auth()->user()?->can('Update:RiskAssessment');
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
