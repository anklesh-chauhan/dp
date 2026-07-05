<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopWorkflows\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkflowRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('step_no')->numeric()->required()->minValue(1),
                Select::make('role_id')->relationship('role', 'name')->searchable()->preload()->required(),
                Select::make('approval_step_type_id')
                    ->relationship('approvalStepType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Same as document department')
                    ->default(fn (): ?int => $this->getOwnerRecord()->department_id),
                Toggle::make('is_mandatory')->default(true),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('step_no')->sortable(),
                TextColumn::make('role.name')->searchable(),
                TextColumn::make('approvalStepType.name')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('Same as document'),
                IconColumn::make('is_mandatory')->boolean(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
