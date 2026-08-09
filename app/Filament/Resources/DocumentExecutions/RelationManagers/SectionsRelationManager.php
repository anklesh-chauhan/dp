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
                TextColumn::make('completedBy.name')->label('Completed by')->placeholder('—'),
                TextColumn::make('verifiedBy.name')->label('Verified by')->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth(Width::Full)
                    ->schema(fn (DocumentExecutionSection $record): array => [
                        ...$this->sectionFormComponents(),
                        $this->executionGrid($record),
                    ])
                    ->mutateRecordDataUsing(function (array $data, DocumentExecutionSection $record): array {
                        $data['execution_rows'] = $this->executionRowsState($record);

                        return $data;
                    })
                    ->mutateDataUsing(function (array $data, DocumentExecutionSection $record): array {
                        $this->updateExecutionRows($record, $data['execution_rows'] ?? []);
                        unset($data['execution_rows']);

                        return $data;
                    })
                    ->visible(fn (): bool => $this->getOwnerRecord() instanceof DocumentExecution
                        && $this->getOwnerRecord()->isEditable()
                        && (auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)),
            ]);
    }

    private function executionGrid(DocumentExecutionSection $section): ExecutionGrid
    {
        $fields = $this->executionFields($section);
        $isScheduledLog = $fields->first()?->source_item_id === null;

        return ExecutionGrid::make('execution_rows')
            ->label('Execution entries')
            ->executionColumns($fields->map(fn (DocumentExecutionItem $field): array => [
                'key' => $this->executionFieldKey($field),
                'label' => $field->label,
                'unit' => $field->unit,
                'required' => $field->is_required,
                'placeholder' => 'Enter '.strtolower($field->label),
            ])->all())
            ->verifiers(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
            ->scheduled($isScheduledLog)
            ->columnSpanFull();
    }

    /** @return Collection<int, DocumentExecutionItem> */
    private function executionFields(DocumentExecutionSection $section): Collection
    {
        $section->loadMissing('items');

        return $section->items
            ->groupBy('row_number')
            ->first()
            ?->values() ?? collect();
    }

    /** @return array<int, array<string, mixed>> */
    private function executionRowsState(DocumentExecutionSection $section): array
    {
        $section->loadMissing('items');

        return $section->items
            ->groupBy('row_number')
            ->map(function ($items, int|string $rowNumber): array {
                $firstItem = $items->first();

                return [
                    'row_label' => $firstItem?->scheduled_at?->toDayDateTimeString() ?? (string) $rowNumber,
                    'responses' => $items->mapWithKeys(fn (DocumentExecutionItem $item): array => [
                        $this->executionFieldKey($item) => $item->response,
                    ])->all(),
                    'comments' => $items->first(fn (DocumentExecutionItem $item): bool => filled($item->comments))?->comments,
                    'verified_by' => $items->first(fn (DocumentExecutionItem $item): bool => filled($item->verified_by))?->verified_by,
                ];
            })
            ->values()
            ->all();
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function updateExecutionRows(DocumentExecutionSection $section, array $rows): void
    {
        $section->loadMissing('execution', 'items');

        DB::transaction(function () use ($section, $rows): void {
            foreach (array_values($rows) as $index => $row) {
                $rowNumber = $index + 1;

                foreach ($section->items->where('row_number', $rowNumber) as $item) {
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
        });
    }

    private function executionFieldKey(DocumentExecutionItem $item): string
    {
        return $item->source_item_id === null ? 'response' : 'field_'.$item->source_item_id;
    }
}
