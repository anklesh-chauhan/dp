<?php

declare(strict_types=1);

namespace App\Filament\Resources\SecurityAuditEvents;

use App\Filament\Resources\SecurityAuditEvents\Pages\ListSecurityAuditEvents;
use App\Filament\Resources\SecurityAuditEvents\Pages\ViewSecurityAuditEvent;
use App\Filament\Resources\SecurityAuditEvents\Schemas\SecurityAuditEventInfolist;
use App\Filament\Resources\SecurityAuditEvents\Tables\SecurityAuditEventsTable;
use App\Models\SecurityAuditEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class SecurityAuditEventResource extends Resource
{
    protected static ?string $model = SecurityAuditEvent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Core · Identity & Access';

    protected static ?int $navigationSort = 70;

    protected static ?string $navigationLabel = 'Security Audit';

    protected static ?string $modelLabel = 'Security Audit Event';

    protected static ?string $pluralModelLabel = 'Security Audit Events';

    protected static ?string $recordTitleAttribute = 'event_uuid';

    public static function infolist(Schema $schema): Schema
    {
        return SecurityAuditEventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SecurityAuditEventsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['actor', 'subjectUser']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecurityAuditEvents::route('/'),
            'view' => ViewSecurityAuditEvent::route('/{record}'),
        ];
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
