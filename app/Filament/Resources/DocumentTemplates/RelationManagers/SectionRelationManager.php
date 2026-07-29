<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\RelationManagers;

use App\Filament\Concerns\ManagesEditableTemplates;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionRelationManager extends RelationManager
{
    use ManagesEditableTemplates;

    protected static string $relationship = 'sections';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([

            Grid::make(2)->schema([
                Select::make('template_version_id')
                    ->relationship('templateVersion', 'version')
                    ->required(),
                TextInput::make('title')->required()->maxLength(255),
                TextInput::make('section_order')->numeric()->required()->minValue(1),
                TextInput::make('section_type')->default('rich_text')->required(),
                Toggle::make('is_required')->default(true),
                RichEditor::make('content')->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('templateVersion.version')->label('Version')->sortable(),
                TextColumn::make('section_order')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('section_type')->badge(),
                IconColumn::make('is_required')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord()),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->canManageTemplateRecord()),
            ]);
    }
}
