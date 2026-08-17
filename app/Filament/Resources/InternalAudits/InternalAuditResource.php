<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalAudits;

use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Models\InternalAudit;
use App\Enums\ProductModule;
use App\Filament\Resources\InternalAudits\Pages\CreateInternalAudit;
use App\Filament\Resources\InternalAudits\Pages\EditInternalAudit;
use App\Filament\Resources\InternalAudits\Pages\ListInternalAudits;
use App\Filament\Resources\InternalAudits\Pages\ViewInternalAudit;
use App\Filament\Resources\InternalAudits\RelationManagers\AuditEventsRelationManager;
use App\Filament\Resources\InternalAudits\RelationManagers\FindingsRelationManager;
use App\Filament\Resources\InternalAudits\Schemas\InternalAuditForm;
use App\Filament\Resources\InternalAudits\Schemas\InternalAuditInfolist;
use App\Filament\Resources\InternalAudits\Tables\InternalAuditsTable;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class InternalAuditResource extends Resource
{
    protected static ?string $model = InternalAudit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'audit_number';

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
        return InternalAuditForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InternalAuditInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternalAuditsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            FindingsRelationManager::class,
            QualityAttachmentsRelationManager::class,
            AuditEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInternalAudits::route('/'),
            'create' => CreateInternalAudit::route('/create'),
            'view' => ViewInternalAudit::route('/{record}'),
            'edit' => EditInternalAudit::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:InternalAudit');
    }

    public static function canView(Model $record): bool
    {
        return (bool) auth()->user()?->can('View:InternalAudit');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:InternalAudit');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof InternalAudit
            && $record->status === InternalAuditStatus::Draft
            && (bool) auth()->user()?->can('Update:InternalAudit');
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
