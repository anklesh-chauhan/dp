<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingPrograms\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TrainingProgramsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('items_count')->counts('items')->label('Documents'),
                TextColumn::make('roleRequirements_count')->counts('roleRequirements')->label('Role links'),
            ])
            ->defaultSort('code');
    }
}
