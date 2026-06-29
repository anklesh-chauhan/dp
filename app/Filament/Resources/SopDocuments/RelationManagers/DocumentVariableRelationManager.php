<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\RelationManagers;

use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentVariableRelationManager extends RelationManager
{
    protected static string $relationship = 'variables';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('variable_name')->required(),
                TextInput::make('value'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('variable_name')->searchable(),
                TextColumn::make('value')->searchable(),
            ])
            ->recordActions([EditAction::make()]);
    }
}
