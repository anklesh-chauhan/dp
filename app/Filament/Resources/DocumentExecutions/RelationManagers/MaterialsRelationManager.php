<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentExecutions\RelationManagers;

use App\Models\DocumentExecution;
use App\Models\DocumentType;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    protected static ?string $title = 'Batch Material Reconciliation';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('material_order')->numeric()->required(),
            TextInput::make('material_name')->required(),
            TextInput::make('material_code'),
            TextInput::make('lot_number'),
            TextInput::make('planned_quantity')->numeric()->required(),
            TextInput::make('actual_quantity')->numeric()->required(),
            TextInput::make('unit')->required(),
            Select::make('verified_by')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('material_name')->searchable(),
                TextColumn::make('material_code'),
                TextColumn::make('lot_number'),
                TextColumn::make('planned_quantity')->numeric(),
                TextColumn::make('actual_quantity')->numeric(),
                TextColumn::make('unit'),
                TextColumn::make('reconciliation')
                    ->state(fn ($record): string => $record->isReconciled() ? 'Reconciled' : 'Mismatch')
                    ->badge(),
                TextColumn::make('verifiedBy.name')->label('Verified by'),
            ])
            ->headerActions([
                CreateAction::make()->visible(fn (): bool => $this->ownerIsEditableBatchRecord()),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (): bool => $this->ownerIsEditableBatchRecord()),
            ]);
    }

    private function ownerIsEditableBatchRecord(): bool
    {
        $execution = $this->getOwnerRecord();

        return $execution instanceof DocumentExecution
            && DocumentType::isBatchRecordCode($execution->document_type_code)
            && $execution->isEditable()
            && (auth()->user()?->can('update', $execution) ?? false);
    }
}
