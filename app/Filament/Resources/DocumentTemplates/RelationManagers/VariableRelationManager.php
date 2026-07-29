<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates\RelationManagers;

use App\Filament\Concerns\ManagesEditableTemplates;
use App\Models\VariableDataType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariableRelationManager extends RelationManager
{
    use ManagesEditableTemplates;

    protected static string $relationship = 'variables';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Grid::make(2)->schema([
                Select::make('template_version_id')
                    ->relationship('templateVersion', 'version')
                    ->required(),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('label')->required()->maxLength(255),
                Select::make('variable_data_type_id')
                    ->relationship('variableDataType', 'name')
                    ->required()
                    ->live(),
                TextInput::make('default_value'),
                Toggle::make('required'),
                KeyValue::make('options')
                    ->label('Options')
                    ->helperText('Value => label pairs for select, multi select, and radio variables.')
                    ->visible(fn (Get $get): bool => self::usesChoiceOptions($get('variable_data_type_id')))
                    ->columnSpanFull(),
                KeyValue::make('validation_rules')->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('templateVersion.version')->label('Version')->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('label')->searchable(),
                TextColumn::make('variableDataType.name')->label('Data Type')->badge(),
                IconColumn::make('required')->boolean(),
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

    private static function usesChoiceOptions(mixed $variableDataTypeId): bool
    {
        if (! is_numeric($variableDataTypeId)) {
            return false;
        }

        $code = VariableDataType::query()->whereKey((int) $variableDataTypeId)->value('code');

        return in_array($code, [
            VariableDataType::SELECT,
            VariableDataType::MULTI_SELECT,
            VariableDataType::RADIO,
        ], true);
    }
}
