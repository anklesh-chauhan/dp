<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingAssignments;

use App\Domain\TMS\Models\TrainingAssignment;
use App\Enums\ProductModule;
use App\Filament\Resources\TrainingAssignments\Pages\ListTrainingAssignments;
use App\Filament\Resources\TrainingAssignments\Tables\TrainingAssignmentsTable;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class TrainingAssignmentResource extends Resource
{
    protected static ?string $model = TrainingAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'TMS';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Training assignments';

    protected static ?string $modelLabel = 'Training assignment';

    protected static ?string $pluralModelLabel = 'Training assignments';

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

    public static function table(Table $table): Table
    {
        return TrainingAssignmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingAssignments::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:TrainingAssignment');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:TrainingAssignment');
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
