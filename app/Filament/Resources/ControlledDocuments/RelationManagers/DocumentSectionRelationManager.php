<?php

declare(strict_types=1);

namespace App\Filament\Resources\ControlledDocuments\RelationManagers;

use App\Filament\Concerns\ManagesEditableDocuments;
use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionItem;
use App\Models\DocumentTemplateSection;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
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
            Select::make('section_type')
                ->label('Section format')
                ->options(DocumentTemplateSection::typeOptions())
                ->default(DocumentTemplateSection::TYPE_TEXT)
                ->required(),
            Select::make('execution_status')
                ->label('Execution status')
                ->options(ControlledDocumentSection::executionStatusOptions())
                ->default(ControlledDocumentSection::STATUS_PENDING)
                ->required(),
            Textarea::make('execution_notes')
                ->label('Completion / exception notes')
                ->helperText('Record observations, exceptions, or the reason for marking a section not applicable.')
                ->rows(3)
                ->required(fn (Get $get): bool => $get('execution_status') === ControlledDocumentSection::STATUS_NOT_APPLICABLE),
            Select::make('verified_by')
                ->label('Verified by')
                ->relationship('verifiedBy', 'name')
                ->searchable()
                ->preload(),
            TextInput::make('heading_level')->label('Heading level')->numeric()->minValue(1)->maxValue(6)->default(1)->required(),
            Toggle::make('include_in_toc')->label('Include in table of contents')->default(true),
            TextInput::make('toc_title')->label('TOC title override')->maxLength(255),
            RichEditor::make('content')
                ->label('Content')
                ->required()
                ->columnSpanFull(),
            KeyValue::make('configuration')
                ->label('Structured field configuration')
                ->helperText('Record the configured columns, units, frequency, or acceptance criteria for this execution section.')
                ->visible(fn (Get $get): bool => in_array($get('section_type'), [DocumentTemplateSection::TYPE_TABLE, DocumentTemplateSection::TYPE_CHECKLIST, DocumentTemplateSection::TYPE_REPEATING_LOG], true))
                ->columnSpanFull(),
            Repeater::make('items')
                ->relationship()
                ->label('Execution items')
                ->schema([
                    TextInput::make('label')
                        ->label('Item')
                        ->required()
                        ->columnSpan(2),
                    TextInput::make('item_order')
                        ->label('Order')
                        ->numeric()
                        ->default(1)
                        ->required(),
                    TextInput::make('response')
                        ->label('Response / reading')
                        ->maxLength(100)
                        ->helperText('For checklists use Pass, Fail, or N/A. For logs, enter the reading.'),
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
                    Textarea::make('comments')
                        ->label('Comments / result')
                        ->rows(2),
                    Toggle::make('is_required')
                        ->label('Required')
                        ->default(true),
                    Select::make('verified_by')
                        ->label('Verified by')
                        ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                ])
                ->columns(5)
                ->visible(fn (Get $get): bool => in_array($get('section_type'), [DocumentTemplateSection::TYPE_TABLE, DocumentTemplateSection::TYPE_CHECKLIST, DocumentTemplateSection::TYPE_REPEATING_LOG], true))
                ->helperText('Add each checklist item, log reading point, or structured row that must be completed.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('section_order')->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('execution_status')->label('Execution')->badge(),
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
