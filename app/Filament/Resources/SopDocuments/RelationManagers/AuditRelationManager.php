<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('action')->badge()->searchable(),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('ip_address'),
                TextColumn::make('user_agent')->limit(60),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
