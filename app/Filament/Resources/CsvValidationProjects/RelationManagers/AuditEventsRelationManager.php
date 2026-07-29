<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class AuditEventsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditEvents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('event_uuid')
            ->columns([
                TextColumn::make('event_uuid')
                    ->searchable(),
                TextColumn::make('from_status')->badge()->placeholder('Created'),
                TextColumn::make('to_status')->badge(),
                TextColumn::make('actor.name')->label('Actor'),
                TextColumn::make('reason')->wrap()->limit(80),
                TextColumn::make('signature_hash')->label('Signed')->formatStateUsing(fn (?string $state): string => $state === null ? 'No' : 'Yes')->badge(),
                TextColumn::make('occurred_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
