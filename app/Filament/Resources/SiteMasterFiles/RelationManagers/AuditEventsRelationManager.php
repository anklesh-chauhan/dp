<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles\RelationManagers;

use App\Enums\ProductModule;
use App\Support\Modules\ModuleManager;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class AuditEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditEvents';

    protected static ?string $title = 'Audit history';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && (bool) auth()->user()?->can('View:SiteMasterFile');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('to_status')->badge(),
                TextColumn::make('actor.name')->label('Actor')->placeholder('—'),
                TextColumn::make('reason')->limit(40)->placeholder('—'),
                TextColumn::make('occurred_at')->dateTime(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
