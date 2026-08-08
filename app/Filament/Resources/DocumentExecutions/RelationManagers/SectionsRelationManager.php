<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentExecutions\RelationManagers;

use App\Models\DocumentExecution;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->disabled(),
            TextInput::make('section_type')->label('Format')->disabled(),
            Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'in_progress' => 'In progress',
                    'completed' => 'Completed',
                    'not_applicable' => 'Not applicable',
                ])
                ->required(),
            Textarea::make('completion_notes')
                ->required(fn (Get $get): bool => $get('status') === 'not_applicable')
                ->columnSpanFull(),
            Select::make('verified_by')
                ->label('Section verified by')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            Repeater::make('items')
                ->relationship()
                ->schema([
                    TextInput::make('scheduled_at')->disabled(),
                    TextInput::make('label')->disabled(),
                    TextInput::make('response')->maxLength(100),
                    Textarea::make('comments')->rows(2),
                    Select::make('verified_by')
                        ->label('Verified by')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable(),
                ])
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columns(5)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('section_order')->label('#')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('section_type')->label('Format')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('completedBy.name')->label('Completed by')->placeholder('—'),
                TextColumn::make('verifiedBy.name')->label('Verified by')->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof DocumentExecution
                        && $this->getOwnerRecord()->isEditable()
                        && (auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)),
            ]);
    }
}
