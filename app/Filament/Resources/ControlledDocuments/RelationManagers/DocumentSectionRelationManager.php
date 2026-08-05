<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Filament\Concerns\ManagesEditableDocuments;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentSectionRelationManager extends RelationManager
{
    use ManagesEditableDocuments;

    protected static string $relationship = 'sections';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('title')
                ->label('Title')
                ->required(),
            TextInput::make('section_order')
                ->label('Section order')
                ->numeric()
                ->required(),
            TextInput::make('heading_level')->label('Heading level')->numeric()->minValue(1)->maxValue(6)->default(1)->required(),
            Toggle::make('include_in_toc')->label('Include in table of contents')->default(true),
            TextInput::make('toc_title')->label('TOC title override')->maxLength(255),
            RichEditor::make('content')
                ->label('Content')
                ->required()
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('section_order')->sortable(),
                TextColumn::make('title')->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->canManageDocumentRecord()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->canManageDocumentRecord()),
            ]);
    }

    public static function canViewForRecord(object $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
