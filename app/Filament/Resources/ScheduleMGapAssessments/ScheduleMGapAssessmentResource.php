<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Enums\ProductModule;
use App\Filament\Resources\ScheduleMGapAssessments\Pages\CreateScheduleMGapAssessment;
use App\Filament\Resources\ScheduleMGapAssessments\Pages\EditScheduleMGapAssessment;
use App\Filament\Resources\ScheduleMGapAssessments\Pages\ListScheduleMGapAssessments;
use App\Filament\Resources\ScheduleMGapAssessments\Pages\ViewScheduleMGapAssessment;
use App\Filament\Resources\ScheduleMGapAssessments\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\ScheduleMGapAssessments\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\ScheduleMGapAssessments\Schemas\ScheduleMGapAssessmentForm;
use App\Filament\Resources\ScheduleMGapAssessments\Schemas\ScheduleMGapAssessmentInfolist;
use App\Filament\Resources\ScheduleMGapAssessments\Tables\ScheduleMGapAssessmentsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class ScheduleMGapAssessmentResource extends Resource
{
    protected static ?string $model = ScheduleMGapAssessment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 18;

    protected static ?string $navigationLabel = 'Schedule M Gap Assessments';

    protected static ?string $modelLabel = 'Schedule M Gap Assessment';

    protected static ?string $recordTitleAttribute = 'assessment_number';

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
        return ScheduleMGapAssessmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ScheduleMGapAssessmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScheduleMGapAssessmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            QualityAttachmentsRelationManager::class,
            AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScheduleMGapAssessments::route('/'),
            'create' => CreateScheduleMGapAssessment::route('/create'),
            'view' => ViewScheduleMGapAssessment::route('/{record}'),
            'edit' => EditScheduleMGapAssessment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:ScheduleMGapAssessment');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:ScheduleMGapAssessment');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:ScheduleMGapAssessment');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ScheduleMGapAssessment
            && in_array($record->status, [
                ScheduleMGapAssessmentStatus::Draft,
                ScheduleMGapAssessmentStatus::InProgress,
            ], true)
            && (bool) auth()->user()?->can('Update:ScheduleMGapAssessment');
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
