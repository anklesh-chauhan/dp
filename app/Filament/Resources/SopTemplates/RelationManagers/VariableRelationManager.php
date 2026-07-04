<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariableRelationManager extends RelationManager
{
    protected static string $relationship = 'variables';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                Select::make('template_version_id')
                    ->relationship('templateVersion', 'version')
                    ->required(),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('label')->required()->maxLength(255),
                Select::make('variable_data_type_id')->relationship('variableDataType', 'name')->required(),
                TextInput::make('default_value'),
                Toggle::make('required'),
                KeyValue::make('validation_rules')->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('templateVersion.version')->label('Version')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('variableDataType.name')->label('Data Type')->badge(),
                IconColumn::make('required')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
