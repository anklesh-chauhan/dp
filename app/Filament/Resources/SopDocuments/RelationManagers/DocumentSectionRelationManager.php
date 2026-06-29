<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\RelationManagers;

use App\Enums\DocumentStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentSectionRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            // Top section: Two-column layout for the basic metadata fields
            Grid::make(2)->schema([
                Select::make('template_version_id') // Adjust to your actual relationship column if needed
                    ->label('Template version')
                    ->required(),

                TextInput::make('title')
                    ->label('Title')
                    ->required(),

                TextInput::make('section_order')
                    ->label('Section order')
                    ->numeric()
                    ->required(),

                TextInput::make('section_type')
                    ->label('Section type')
                    ->required(),

                Toggle::make('is_required')
                    ->label('Is required')
                    ->default(true)
                    ->columnSpanFull(), // Pushes the toggle to its own line
            ]),

            // Bottom section: Kept separate from the grid so it seamlessly spans 100% width
            RichEditor::make('content')
                ->label('Content')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('section_order')->sortable(),
                TextColumn::make('title')->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(DocumentStatus::options()),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }
}
