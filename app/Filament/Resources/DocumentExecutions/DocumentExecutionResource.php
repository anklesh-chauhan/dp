<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentExecutions;

use App\Filament\Resources\DocumentExecutions\Pages\EditDocumentExecution;
use App\Filament\Resources\DocumentExecutions\Pages\ListDocumentExecutions;
use App\Filament\Resources\DocumentExecutions\Pages\ViewDocumentExecution;
use App\Filament\Resources\DocumentExecutions\RelationManagers\MaterialsRelationManager;
use App\Filament\Resources\DocumentExecutions\RelationManagers\SectionsRelationManager;
use App\Filament\Resources\Shared\RelationManagers\QualityAttachmentsRelationManager;
use App\Models\DocumentExecution;
use App\Models\DocumentType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DocumentExecutionResource extends Resource
{
    protected static ?string $model = DocumentExecution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'DMS';

    protected static ?string $navigationLabel = 'GMP Execution Records';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'execution_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('execution_number')->disabled(),
            TextInput::make('document_number')->disabled(),
            TextInput::make('document_version')->label('Master version')->disabled(),
            TextInput::make('document_type_code')->label('Record type')->disabled(),
            Select::make('status')->options(self::statusOptions())->disabled(),
            TextInput::make('batch_number')
                ->visible(fn (?DocumentExecution $record): bool => DocumentType::isBatchRecordCode($record?->document_type_code)),
            TextInput::make('product_name')
                ->visible(fn (?DocumentExecution $record): bool => DocumentType::isBatchRecordCode($record?->document_type_code)),
            Select::make('log_frequency')
                ->options(['hourly' => 'Hourly', 'shift' => 'Every shift', 'daily' => 'Daily'])
                ->visible(fn (?DocumentExecution $record): bool => DocumentType::isRepeatingLogCode($record?->document_type_code)),
            DatePicker::make('log_period_start')
                ->visible(fn (?DocumentExecution $record): bool => DocumentType::isRepeatingLogCode($record?->document_type_code)),
            DatePicker::make('log_period_end')
                ->afterOrEqual('log_period_start')
                ->visible(fn (?DocumentExecution $record): bool => DocumentType::isRepeatingLogCode($record?->document_type_code)),
            Select::make('supervisor_id')->relationship('supervisor', 'name')->searchable()->preload(),
            Textarea::make('review_notes')->disabled()->columnSpanFull(),
            Textarea::make('qa_notes')->disabled()->columnSpanFull(),
            Select::make('disposition')->options([
                DocumentExecution::DISPOSITION_NOT_APPLICABLE => 'Not applicable',
                DocumentExecution::DISPOSITION_PENDING => 'Pending',
                DocumentExecution::DISPOSITION_RELEASED => 'Released',
                DocumentExecution::DISPOSITION_REJECTED => 'Rejected',
            ])->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('execution_number')->searchable()->sortable(),
                TextColumn::make('document_number')->label('Master')->searchable(),
                TextColumn::make('document_version')->label('Version'),
                TextColumn::make('document_type_code')->label('Type')->badge(),
                TextColumn::make('issuance.issuance_number')->label('Issued copy'),
                TextColumn::make('status')->badge(),
                TextColumn::make('progress')
                    ->state(function (DocumentExecution $record): string {
                        $summary = $record->executionSummary();

                        return $summary['total'] === 0 ? '—' : "{$summary['completed']}/{$summary['total']}";
                    }),
                TextColumn::make('disposition')->badge(),
                TextColumn::make('completed_at')->dateTime()->toggleable(),
                TextColumn::make('closed_at')->dateTime()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::statusOptions()),
                SelectFilter::make('document_type_code')->options([
                    DocumentType::FORM => 'Form',
                    DocumentType::LOG => 'Log',
                    DocumentType::CHECKLIST => 'Checklist',
                    DocumentType::BATCH_RECORD => 'BMR',
                    DocumentType::BATCH_PACKAGING_RECORD => 'BPR',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (DocumentExecution $record): bool => $record->isEditable()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SectionsRelationManager::class,
            MaterialsRelationManager::class,
            QualityAttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentExecutions::route('/'),
            'view' => ViewDocumentExecution::route('/{record}'),
            'edit' => EditDocumentExecution::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['issuance', 'sections']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        return [
            DocumentExecution::STATUS_ISSUED => 'Issued',
            DocumentExecution::STATUS_IN_PROGRESS => 'In progress',
            DocumentExecution::STATUS_COMPLETED => 'Completed',
            DocumentExecution::STATUS_UNDER_REVIEW => 'Under supervisor review',
            DocumentExecution::STATUS_QA_REVIEW => 'Under QA review',
            DocumentExecution::STATUS_CLOSED => 'Closed',
        ];
    }
}
