<?php

declare(strict_types=1);

namespace App\Filament\Resources\QualityApprovalWorkflows\RelationManagers;

use App\Domain\QMS\Models\QualityApprovalWorkflow;
use Filament\Actions\ActionGroup;
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
use Illuminate\Validation\Rules\Unique;

final class WorkflowStepsRelationManager extends RelationManager
{
    protected static string $relationship = 'steps';

    protected static ?string $title = 'Steps';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                TextInput::make('step_no')
                    ->label('Step number')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->default(fn (): int => (int) $this->getOwnerRecord()->steps()->max('step_no') + 1)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->where(
                            'workflow_id',
                            $this->getOwnerRecord()->getKey(),
                        ),
                    ),
                Select::make('role_id')
                    ->relationship('role', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Same as record department')
                    ->default(fn (): mixed => $this->getOwnerRecord()->department_id),
                Toggle::make('is_mandatory')
                    ->label('Mandatory')
                    ->default(true),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('step_no')
            ->columns([
                TextColumn::make('step_no')->label('Step')->sortable(),
                TextColumn::make('role.name')->searchable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->placeholder('Same as record'),
                IconColumn::make('is_mandatory')->label('Mandatory')->boolean(),
            ])
            ->defaultSort('step_no')
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->isDefinitionMutable()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->visible(fn (): bool => $this->getOwnerRecord()->isDefinitionMutable()),
                    DeleteAction::make()
                        ->visible(fn (): bool => $this->getOwnerRecord()->isDefinitionMutable()),
                ])->icon('heroicon-o-ellipsis-vertical'),
            ]);
    }

    public function getOwnerRecord(): QualityApprovalWorkflow
    {
        $owner = parent::getOwnerRecord();

        assert($owner instanceof QualityApprovalWorkflow);

        return $owner;
    }
}
