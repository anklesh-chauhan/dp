<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Filament\Concerns\ManagesEditableDocuments;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionItem;
use App\Models\DocumentTemplateSection;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            Select::make('section_type')
                ->label('Section format')
                ->options(DocumentTemplateSection::typeOptions())
                ->default(DocumentTemplateSection::TYPE_TEXT)
                ->required(),
            TextInput::make('heading_level')->label('Heading level')->numeric()->minValue(1)->maxValue(6)->default(1)->required(),
            Toggle::make('include_in_toc')->label('Include in table of contents')->default(true),
            TextInput::make('toc_title')->label('TOC title override')->maxLength(255),
            RichEditor::make('content')
                ->label('Content')
                ->required()
                ->columnSpanFull(),
            Section::make('Execution tables')
                ->schema([
                    Repeater::make('executionTables')
                        ->relationship()
                        ->label('Tables')
                        ->orderColumn('table_order')
                        ->defaultItems(0)
                        ->minItems(1)
                        ->addActionLabel('Add another table')
                        ->schema([
                            TextInput::make('title')
                                ->label('Table title')
                                ->placeholder('For example: Container details')
                                ->required()
                                ->columnSpan(2),
                            Select::make('execution_layout')
                                ->label('Print layout')
                                ->options([
                                    'table' => 'Table - fields are columns',
                                    'field_value' => 'Form - fields are vertical rows',
                                ])
                                ->default('table')
                                ->required(),
                            TextInput::make('row_count')
                                ->label('Number of rows')
                                ->helperText('Writable rows beneath this table header.')
                                ->numeric()
                                ->integer()
                                ->minValue(1)
                                ->maxValue(100)
                                ->default(1)
                                ->required(),
                            Repeater::make('items')
                                ->relationship()
                                ->label('Columns')
                                ->orderColumn('item_order')
                                ->defaultItems(0)
                                ->minItems(1)
                                ->addActionLabel('Add column')
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Column header')
                                        ->required()
                                        ->columnSpan(2),
                                    Select::make('value_type')
                                        ->label('Value type')
                                        ->options([
                                            ControlledDocumentSectionItem::VALUE_TEXT => 'Text',
                                            ControlledDocumentSectionItem::VALUE_NUMERIC => 'Numeric',
                                            ControlledDocumentSectionItem::VALUE_BOOLEAN => 'Pass / Fail',
                                        ])
                                        ->default(ControlledDocumentSectionItem::VALUE_TEXT)
                                        ->required(),
                                    TextInput::make('unit')->label('Unit')->maxLength(30),
                                    TextInput::make('decimal_precision')->label('Decimals')->numeric()->minValue(0)->maxValue(8),
                                    Select::make('acceptance_operator')
                                        ->label('Acceptance')
                                        ->options([
                                            'between' => 'Between minimum and maximum',
                                            'gte' => 'Greater than or equal to minimum',
                                            'lte' => 'Less than or equal to maximum',
                                            'eq' => 'Equal to minimum',
                                        ])
                                        ->visible(fn (Get $get): bool => $get('value_type') === ControlledDocumentSectionItem::VALUE_NUMERIC),
                                    TextInput::make('acceptance_min')->label('Minimum / target')->numeric()->visible(fn (Get $get): bool => $get('value_type') === ControlledDocumentSectionItem::VALUE_NUMERIC),
                                    TextInput::make('acceptance_max')->label('Maximum')->numeric()->visible(fn (Get $get): bool => $get('value_type') === ControlledDocumentSectionItem::VALUE_NUMERIC),
                                    Toggle::make('is_required')
                                        ->label('Required')
                                        ->default(true),
                                ])
                                ->columns(5)
                                ->columnSpanFull(),
                        ])
                        ->columns(4)
                        ->helperText('Add as many tables as the section needs. Each table has its own title, layout, row count, and columns.'),
                ])
                ->visible(fn (Get $get): bool => in_array($get('section_type'), [DocumentTemplateSection::TYPE_TABLE, DocumentTemplateSection::TYPE_CHECKLIST], true))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['items', 'executionTables']))
            ->columns([
                TextColumn::make('section_order')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('field_definitions')
                    ->label('Execution fields')
                    ->state(fn (ControlledDocumentSection $record): string => $record->requiresFieldDefinitions()
                        ? (string) $record->getAttribute('items_count')
                        : 'N/A')
                    ->badge()
                    ->color(fn (ControlledDocumentSection $record): string => match (true) {
                        ! $record->requiresFieldDefinitions() => 'gray',
                        ((int) $record->getAttribute('items_count')) > 0 => 'success',
                        default => 'danger',
                    }),
                TextColumn::make('table_definitions')
                    ->label('Tables')
                    ->state(fn (ControlledDocumentSection $record): string => $record->requiresFieldDefinitions()
                        ? (string) $record->getAttribute('execution_tables_count')
                        : 'N/A')
                    ->badge(),
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
