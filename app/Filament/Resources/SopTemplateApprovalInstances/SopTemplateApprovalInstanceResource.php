<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplateApprovalInstances;

use App\Filament\Resources\SopTemplateApprovalInstances\Pages\ListSopTemplateApprovalInstances;
use App\Filament\Resources\SopTemplateApprovalInstances\Pages\ViewSopTemplateApprovalInstance;
use App\Filament\Resources\SopTemplateApprovalInstances\Schemas\SopTemplateApprovalInstanceInfolist;
use App\Filament\Resources\SopTemplateApprovalInstances\Tables\SopTemplateApprovalInstancesTable;
use App\Models\SopTemplateApprovalInstance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SopTemplateApprovalInstanceResource extends Resource
{
    protected static ?string $model = SopTemplateApprovalInstance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'DMS · Document Control';

    protected static ?string $navigationLabel = 'Template Approval Queue';

    protected static ?string $modelLabel = 'Template Approval';

    protected static ?string $pluralModelLabel = 'Template Approval Queue';

    public static function infolist(Schema $schema): Schema
    {
        return SopTemplateApprovalInstanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SopTemplateApprovalInstancesTable::configure($table);
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
            'index' => ListSopTemplateApprovalInstances::route('/'),
            'view' => ViewSopTemplateApprovalInstance::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'templateVersion.template',
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
        return (bool) auth()->user()?->can('ViewAny:SopTemplateApproval');
    }

    public static function canView(mixed $record): bool
    {
        return (bool) auth()->user()?->can('View:SopTemplateApproval');
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
