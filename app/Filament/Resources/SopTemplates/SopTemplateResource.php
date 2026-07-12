<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates;

use App\Filament\Concerns\HasGenerationPolling;
use App\Filament\Resources\SopTemplates\Pages\CreateSopTemplate;
use App\Filament\Resources\SopTemplates\Pages\EditSopTemplate;
use App\Filament\Resources\SopTemplates\Pages\ListSopTemplates;
use App\Filament\Resources\SopTemplates\Pages\ViewSopTemplate;
use App\Filament\Resources\SopTemplates\RelationManagers\SectionRelationManager;
use App\Filament\Resources\SopTemplates\RelationManagers\TemplateAuditRelationManager;
use App\Filament\Resources\SopTemplates\RelationManagers\VariableRelationManager;
use App\Filament\Resources\SopTemplates\RelationManagers\VersionRelationManager;
use App\Filament\Support\DocumentClassificationFormFields;
use App\Models\AiTask;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\View;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use UnitEnum;

class SopTemplateResource extends Resource
{
    use HasGenerationPolling;

    protected static ?string $model = SopTemplate::class;

    protected static string|UnitEnum|null $navigationGroup = 'SOP Management';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return strval(static::getModel()::count());
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template Details')
                ->schema([

                    Section::make('AI Generation Tracker')
                        ->icon('heroicon-m-sparkles')
                        ->collapsible()
                        ->visible(fn (?SopTemplate $record): bool => $record?->isGenerationInProgress() ?? false)
                        ->schema([
                            Placeholder::make('progress_bar')
                                ->hiddenLabel()
                                ->content(fn (SopTemplate $record): HtmlString => new HtmlString("
                                    <div wire:poll.3s class='space-y-3 p-2'>
                                        <div class='flex justify-between items-center text-sm font-medium'>
                                            <span class='text-primary-600 dark:text-primary-400 animate-pulse flex items-center gap-2'>
                                                ".Blade::render('<x-filament::loading-indicator class="h-5 w-5" />')."
                                                Current Step: <span class='font-semibold'>".e($record->generation_status)."</span>
                                            </span>
                                            <span class='font-bold text-gray-700 dark:text-gray-300'>".(int) $record->generation_progress."%</span>
                                        </div>
                                        <div class='w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden'>
                                            <div class='bg-primary-600 h-2.5 rounded-full transition-all duration-500' style='width: ".(int) $record->generation_progress."%'></div>
                                        </div>
                                        <p class='text-xs text-gray-400 dark:text-gray-500'>
                                            Your laptop's CPU is processing regulatory logic locally. The structural sections and variables will reveal themselves automatically when finished.
                                        </p>
                                    </div>
                                ")),
                        ]),

                    Grid::make(3)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),

                        TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule),

                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        View::make(
                                'filament.sop-templates.metadata-ai-progress',
                            )
                                ->visible(
                                    fn (Component $livewire): bool =>
                                        $livewire->metadataAiTaskPolling
                                )
                                ->columnSpanFull(),

                        Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(5)
                            ->hintAction(
                                Action::make('generateMetadataWithAi')
                                    ->label('Generate with AI')
                                    ->icon('heroicon-m-sparkles')
                                    ->visible(
                                        fn (callable $get): bool =>
                                            filled($get('name'))
                                            && filled($get('department_id'))
                                    )
                                    ->disabled(
                                        fn (Component $livewire): bool =>
                                            $livewire->metadataAiTaskPolling
                                    )
                                    ->action(
                                        fn (Component $livewire) =>
                                            $livewire->startMetadataAiGeneration()
                                    )
                            ),

                        ...DocumentClassificationFormFields::templateFields(),

                        Select::make('template_status_id')
                            ->relationship('templateStatus', 'name')
                            ->default(fn (): int => TemplateStatus::idFor(TemplateStatus::DRAFT))
                            ->required(),

                        TextInput::make('current_version')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                    ]),
                ])
                ->disabled(fn (?SopTemplate $record): bool => $record?->isGenerationInProgress() ?? false)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('department.name')->searchable()->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('documentType.name')
                    ->label('Document Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('regulationTags.name')
                    ->label('Regulation Tags')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('templateStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (SopTemplate $record): string => match ($record->templateStatus?->code) {
                        TemplateStatus::DRAFT => 'gray',
                        TemplateStatus::PUBLISHED => 'success',
                        TemplateStatus::OBSOLETE => 'warning',
                        TemplateStatus::ARCHIVED => 'gray',
                        TemplateStatus::RETENTION_COMPLETED => 'gray',
                        TemplateStatus::DESTROYED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('current_version')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category_id')->relationship('category', 'name')->label('Category'),
                SelectFilter::make('document_type_id')->relationship('documentType', 'name')->label('Document Type'),
                SelectFilter::make('regulationTags')->relationship('regulationTags', 'name')->label('Regulation Tag'),
                SelectFilter::make('template_status_id')->relationship('templateStatus', 'name')->label('Status'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            VersionRelationManager::class,
            SectionRelationManager::class,
            VariableRelationManager::class,
            TemplateAuditRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSopTemplates::route('/'),
            'create' => CreateSopTemplate::route('/create'),
            'view' => ViewSopTemplate::route('/{record}'),
            'edit' => EditSopTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['department', 'category', 'documentType', 'regulationTags', 'templateStatus']);
    }

    private static function runAiClassification(
        Component $livewire,
        Set $set,
        ?string $description = null,
    ): void {
        if (! method_exists($livewire, 'classifyFromMetadata')) {
            return;
        }

        $livewire->classifyFromMetadata(
            set: $set,
            description: $description,
        );
    }

    private static function runAiDescriptionGeneration(
        Component $livewire,
        Set $set,
    ): ?string {
        if (! method_exists($livewire, 'generateDescriptionFromMetadata')) {
            return null;
        }

        return $livewire->generateDescriptionFromMetadata($set);
    }
}
