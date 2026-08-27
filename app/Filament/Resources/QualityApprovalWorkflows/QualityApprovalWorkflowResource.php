<?php

declare(strict_types=1);

namespace App\Filament\Resources\QualityApprovalWorkflows;

use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Enums\ProductModule;
use App\Filament\Resources\QualityApprovalWorkflows\Pages\CreateQualityApprovalWorkflow;
use App\Filament\Resources\QualityApprovalWorkflows\Pages\EditQualityApprovalWorkflow;
use App\Filament\Resources\QualityApprovalWorkflows\Pages\ListQualityApprovalWorkflows;
use App\Filament\Resources\QualityApprovalWorkflows\Pages\ViewQualityApprovalWorkflow;
use App\Filament\Resources\QualityApprovalWorkflows\RelationManagers\WorkflowStepsRelationManager;
use App\Filament\Resources\QualityApprovalWorkflows\Schemas\QualityApprovalWorkflowForm;
use App\Filament\Resources\QualityApprovalWorkflows\Schemas\QualityApprovalWorkflowInfolist;
use App\Filament\Resources\QualityApprovalWorkflows\Tables\QualityApprovalWorkflowsTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class QualityApprovalWorkflowResource extends Resource
{
    protected static ?string $model = QualityApprovalWorkflow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'quality-workflows';

    protected static ?string $navigationLabel = 'Quality Workflows';

    protected static ?string $modelLabel = 'quality workflow';

    protected static ?string $pluralModelLabel = 'quality workflows';

    protected static string|array $routeMiddleware = ['module:qms'];

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
        return QualityApprovalWorkflowForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QualityApprovalWorkflowInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QualityApprovalWorkflowsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            WorkflowStepsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQualityApprovalWorkflows::route('/'),
            'create' => CreateQualityApprovalWorkflow::route('/create'),
            'view' => ViewQualityApprovalWorkflow::route('/{record}'),
            'edit' => EditQualityApprovalWorkflow::route('/{record}/edit'),
        ];
    }

    public static function canDelete(mixed $record): bool
    {
        return $record instanceof QualityApprovalWorkflow
            && $record->approvalInstances()->doesntExist()
            && parent::canDelete($record);
    }
}
