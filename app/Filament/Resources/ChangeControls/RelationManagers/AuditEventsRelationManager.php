<?php

declare(strict_types=1);

namespace App\Filament\Resources\ChangeControls\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AuditEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditEvents';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')->dateTime()->sortable(),
                TextColumn::make('from_status')->badge(),
                TextColumn::make('to_status')->badge(),
                TextColumn::make('actor.name')->label('Actor')->placeholder('System'),
                TextColumn::make('reason')->wrap()->placeholder('—'),
            ])
            ->defaultSort('id', 'desc');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
