<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RisksRelationManager extends RelationManager
{
    protected static string $relationship = 'risks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('risk_identifier')
                    ->required()
                    ->maxLength(255),
                Select::make('csv_requirement_id')->relationship('requirement', 'requirement_identifier')->searchable()->preload(),
                Textarea::make('hazard')->required(),
                Textarea::make('potential_impact')->required(),
                Textarea::make('existing_controls'),
                TextInput::make('initial_severity')->numeric()->minValue(1)->maxValue(5)->required(),
                TextInput::make('initial_probability')->numeric()->minValue(1)->maxValue(5)->required(),
                TextInput::make('initial_detectability')->numeric()->minValue(1)->maxValue(5)->required(),
                Textarea::make('mitigation'),
                TextInput::make('residual_severity')->numeric()->minValue(1)->maxValue(5),
                TextInput::make('residual_probability')->numeric()->minValue(1)->maxValue(5),
                TextInput::make('residual_detectability')->numeric()->minValue(1)->maxValue(5),
                Textarea::make('acceptance_rationale'),
                Select::make('accepted_by')->relationship('acceptor', 'name')->searchable()->preload(),
                DateTimePicker::make('accepted_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('risk_identifier')
            ->columns([
                TextColumn::make('risk_identifier')
                    ->searchable(),
                TextColumn::make('requirement.requirement_identifier')->label('Requirement'),
                TextColumn::make('initial_rpn')->state(fn ($record): int => $record->initialRiskPriorityNumber())->label('Initial RPN'),
                TextColumn::make('residual_rpn')->state(fn ($record): ?int => $record->residualRiskPriorityNumber())->label('Residual RPN'),
                TextColumn::make('accepted_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
