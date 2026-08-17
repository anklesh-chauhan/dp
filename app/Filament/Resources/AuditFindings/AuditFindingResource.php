<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditFindings;

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Models\AuditFinding;
use App\Enums\ProductModule;
use App\Filament\Resources\AuditFindings\Pages\CreateAuditFinding;
use App\Filament\Resources\AuditFindings\Pages\EditAuditFinding;
use App\Filament\Resources\AuditFindings\Pages\ListAuditFindings;
use App\Filament\Resources\AuditFindings\Pages\ViewAuditFinding;
use App\Filament\Resources\AuditFindings\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\AuditFindings\Schemas\AuditFindingForm;
use App\Filament\Resources\AuditFindings\Schemas\AuditFindingInfolist;
use App\Filament\Resources\AuditFindings\Tables\AuditFindingsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class AuditFindingResource extends Resource
{
    protected static ?string $model = AuditFinding::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'finding_number';

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
        return AuditFindingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AuditFindingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuditFindingsTable::configure($table);
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
            'index' => ListAuditFindings::route('/'),
            'create' => CreateAuditFinding::route('/create'),
            'view' => ViewAuditFinding::route('/{record}'),
            'edit' => EditAuditFinding::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:AuditFinding');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:AuditFinding');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:AuditFinding');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof AuditFinding
            && $record->disposition === AuditFindingDisposition::Open
            && (bool) auth()->user()?->can('Update:AuditFinding');
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
