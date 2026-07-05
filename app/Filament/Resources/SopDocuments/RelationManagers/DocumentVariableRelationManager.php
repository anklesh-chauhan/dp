<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments\RelationManagers;

use App\Filament\Concerns\ManagesEditableDocuments;
use App\Filament\Support\TemplateVariableFieldBuilder;
use App\Models\SopDocumentVariable;
use App\Models\SopTemplateVariable;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentVariableRelationManager extends RelationManager
{
    use ManagesEditableDocuments;

    protected static string $relationship = 'variables';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(fn (?SopDocumentVariable $record): array => [
                Grid::make(2)->schema([
                    TextInput::make('variable_name')->required()->disabled(),
                    ...$this->valueFields($record),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('variable_name')->searchable(),
                TextColumn::make('value')->searchable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->canManageDocumentRecord()),
            ]);
    }

    /**
     * @return array<int, Field>
     */
    private function valueFields(?SopDocumentVariable $record): array
    {
        $templateVariable = $this->resolveTemplateVariable($record);

        if ($templateVariable === null) {
            return [TextInput::make('value')];
        }

        return [TemplateVariableFieldBuilder::editField($templateVariable, $record?->document?->template_id)];
    }

    private function resolveTemplateVariable(?SopDocumentVariable $record): ?SopTemplateVariable
    {
        if ($record === null) {
            return null;
        }

        $record->loadMissing('document.templateVersion.variables.variableDataType');

        return $record->document?->templateVersion?->variables
            ->firstWhere('name', $record->variable_name);
    }
}
