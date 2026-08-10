<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplateApprovalInstances;

use App\Domain\DMS\Services\TemplateApprovalDecisionService;
use App\Filament\Resources\DocumentTemplateApprovalInstances\Pages\ListDocumentTemplateApprovalInstances;
use App\Filament\Resources\DocumentTemplateApprovalInstances\Pages\ViewDocumentTemplateApprovalInstance;
use App\Filament\Resources\DocumentTemplateApprovalInstances\Schemas\DocumentTemplateApprovalInstanceInfolist;
use App\Filament\Resources\DocumentTemplateApprovalInstances\Tables\DocumentTemplateApprovalInstancesTable;
use App\Models\DocumentTemplateApprovalInstance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DocumentTemplateApprovalInstanceResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = DocumentTemplateApprovalInstance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'DMS';

    protected static ?string $navigationLabel = 'Template Approval Queue';

    protected static ?string $modelLabel = 'Template Approval';

    protected static ?string $pluralModelLabel = 'Template Approval Queue';

    public static function infolist(Schema $schema): Schema
    {
        return DocumentTemplateApprovalInstanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentTemplateApprovalInstancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentTemplateApprovalInstances::route('/'),
            'view' => ViewDocumentTemplateApprovalInstance::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'templateVersion.template',
            'templateVersion.approvalEvents',
            'templateVersion.submitter',
            'workflow',
            'workflowStep.role',
            'workflowStep.approvalStepType',
            'decider',
        ]);
        $user = auth()->user();

        if ($user === null || $user->hasRole('sop administrator')) {
            return $query;
        }

        return $query->whereHas(
            'workflowStep.role',
            fn (Builder $roleQuery): Builder => $roleQuery->whereIn('id', $user->roles->modelKeys()),
        );
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:DocumentTemplateApproval');
    }

    public static function canView(mixed $record): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->can('View:DocumentTemplateApproval')
            || app(TemplateApprovalDecisionService::class)->canDecide($record, $user);
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
}
