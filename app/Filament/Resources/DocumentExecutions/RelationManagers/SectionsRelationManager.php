<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentExecutions\RelationManagers;

use App\Filament\Forms\Components\ExecutionGrid;
use App\Models\DocumentExecution;
use App\Models\DocumentExecutionItem;
use App\Models\DocumentExecutionSection;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SectionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sections';

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->sectionFormComponents());
    }

    /** @return array<int, mixed> */
    private function sectionFormComponents(): array
    {
        return [
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
        ];
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
                TextColumn::make('completedBy.name')->label('Completed by')->placeholder('â€”'),
                TextColumn::make('verifiedBy.name')->label('Verified by')->placeholder('â€”'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth(Width::Full)
                    ->schema(fn (DocumentExecutionSection $record): array => [
                        ...$this->sectionFormComponents(),
                        ...$this->executionGrids($record),
                    ])
                    ->mutateRecordDataUsing(function (array $data, DocumentExecutionSection $record): array {
                        $data['execution_tables'] = $this->executionTablesState($record);

                        return $data;
                    })
                    ->mutateDataUsing(function (array $data, DocumentExecutionSection $record): array {
                        $this->updateExecutionTables($record, $data['execution_tables'] ?? []);
                        unset($data['execution_tables']);

                        return $data;
                    })
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof DocumentExecution
                        && $this->getOwnerRecord()->isEditable()
                        && (auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)),
            ]);
    }

    /** @return array<int, ExecutionGrid> */
    private function executionGrids(DocumentExecutionSection $section): array
    {
        return $this->executionTableGroups($section)
            ->map(function (Collection $items, string $tableKey): ExecutionGrid {
                $title = $items->first()?->table_title;

                return $this->executionGrid(
                    items: $items,
                    statePath: 'execution_tables.'.$tableKey,
                    label: filled($title) ? $title : 'Execution entries',
                );
            })
            ->values()
            ->all();
    }

    private function executionGrid(Collection $items, string $statePath, string $label): ExecutionGrid
    {
        $fields = $this->executionFields($items);
        $isScheduledLog = $fields->first()?->source_item_id === null;

        return ExecutionGrid::make($statePath)
            ->label($label)
            ->executionColumns($fields->map(fn (DocumentExecutionItem $field): array => [
                'key' => $this->executionFieldKey($field),
                'label' => $field->label,
                'value_type' => $field->value_type,
                'unit' => $field->unit,
                'decimal_precision' => $field->decimal_precision,
                'step' => $this->numericStep($field->decimal_precision),
                'required' => $field->is_required,
                'placeholder' => 'Enter '.strtolower($field->label),
            ])->all())
            ->verifiers(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
            ->scheduled($isScheduledLog)
            ->columnSpanFull();
    }

    /** @return Collection<int, DocumentExecutionItem> */
    private function executionFields(Collection $items): Collection
    {
        return $items
            ->groupBy('row_number')
            ->first()
            ?->values() ?? collect();
    }

    /** @return Collection<string, Collection<int, DocumentExecutionItem>> */
    private function executionTableGroups(DocumentExecutionSection $section): Collection
    {
        $section->loadMissing('items');

        return $section->items->groupBy(fn (DocumentExecutionItem $item): string => $this->executionTableKey($item));
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function executionTablesState(DocumentExecutionSection $section): array
    {
        return $this->executionTableGroups($section)
            ->map(fn (Collection $items): array => $this->executionRowsState($items))
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function executionRowsState(Collection $items): array
    {
        return $items
            ->groupBy('row_number')
            ->map(function (Collection $rowItems, int|string $rowNumber): array {
                $firstItem = $rowItems->first();

                return [
                    'row_label' => $firstItem?->scheduled_at?->toDayDateTimeString() ?? (string) $rowNumber,
                    'responses' => $rowItems->mapWithKeys(fn (DocumentExecutionItem $item): array => [
                        $this->executionFieldKey($item) => $item->response,
                    ])->all(),
                    'comments' => $rowItems->first(fn (DocumentExecutionItem $item): bool => filled($item->comments))?->comments,
                    'verified_by' => $rowItems->first(fn (DocumentExecutionItem $item): bool => filled($item->verified_by))?->verified_by,
                ];
            })
            ->values()
            ->all();
    }

    /** @param array<string, array<int, array<string, mixed>>> $tables */
    private function updateExecutionTables(DocumentExecutionSection $section, array $tables): void
    {
        $section->loadMissing('execution', 'items');
        $groups = $this->executionTableGroups($section);

        DB::transaction(function () use ($section, $tables, $groups): void {
            foreach ($tables as $tableKey => $rows) {
                $items = $groups->get($tableKey, collect());

                foreach (array_values($rows) as $index => $row) {
                    $rowNumber = $index + 1;

                    foreach ($items->where('row_number', $rowNumber) as $item) {
                        $item->setRelation('section', $section);
                        $item->fill([
                            'response' => data_get($row, 'responses.'.$this->executionFieldKey($item)),
                            'comments' => $row['comments'] ?? null,
                            'verified_by' => $row['verified_by'] ?? null,
                        ]);

                        if ($item->isDirty(['response', 'comments', 'verified_by'])) {
                            $item->save();
                        }
                    }
                }
            }
        });
    }

    private function executionTableKey(DocumentExecutionItem $item): string
    {
        return $item->source_table_id === null && blank($item->table_title)
            ? 'legacy'
            : 'table_'.$item->table_order;
    }

    private function executionFieldKey(DocumentExecutionItem $item): string
    {
        return $item->source_item_id === null ? 'response' : 'field_'.$item->source_item_id;
    }

    private function numericStep(?int $decimalPrecision): string
    {
        if ($decimalPrecision === null) {
            return 'any';
        }

        if ($decimalPrecision === 0) {
            return '1';
        }

        return '0.'.Str::repeat('0', $decimalPrecision - 1).'1';
    }
}
