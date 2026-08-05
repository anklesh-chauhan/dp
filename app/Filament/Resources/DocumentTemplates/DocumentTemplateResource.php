<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplates;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Enums\ProductModule;
use App\Filament\Concerns\HasGenerationPolling;
use App\Filament\Resources\DocumentTemplates\Pages\CreateDocumentTemplate;
use App\Filament\Resources\DocumentTemplates\Pages\EditDocumentTemplate;
use App\Filament\Resources\DocumentTemplates\Pages\ListDocumentTemplates;
use App\Filament\Resources\DocumentTemplates\Pages\ViewDocumentTemplate;
use App\Filament\Resources\DocumentTemplates\RelationManagers\ApprovalEventsRelationManager;
use App\Filament\Resources\DocumentTemplates\RelationManagers\ApprovalInstancesRelationManager;
use App\Filament\Resources\DocumentTemplates\RelationManagers\SectionRelationManager;
use App\Filament\Resources\DocumentTemplates\RelationManagers\TemplateAuditRelationManager;
use App\Filament\Resources\DocumentTemplates\RelationManagers\VariableRelationManager;
use App\Filament\Resources\DocumentTemplates\RelationManagers\VersionRelationManager;
use App\Filament\Support\DocumentClassificationFormFields;
use App\Models\DocumentTemplate;
use App\Models\ReportTemplate;
use App\Models\TemplateStatus;
use App\Support\Modules\ModuleManager;
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
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
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

class DocumentTemplateResource extends Resource
{
    use HasGenerationPolling;

    protected static ?string $model = DocumentTemplate::class;

    protected static string|UnitEnum|null $navigationGroup = 'DMS';

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
                        ->visible(fn (?DocumentTemplate $record): bool => app(ModuleManager::class)->enabled(ProductModule::AI)
                            && ($record?->isGenerationInProgress() ?? false))
                        ->schema([
                            Placeholder::make('progress_bar')
                                ->hiddenLabel()
                                ->content(fn (DocumentTemplate $record): HtmlString => new HtmlString("
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
                            'filament.document-templates.metadata-ai-progress',
                        )
                            ->visible(
                                fn (Component $livewire): bool => $livewire->metadataAiTaskPolling
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
                                        fn (callable $get): bool => app(ModuleManager::class)->enabled(ProductModule::AI)
                                            && filled($get('name'))
                                            && filled($get('department_id'))
                                    )
                                    ->disabled(
                                        fn (Component $livewire): bool => $livewire->metadataAiTaskPolling
                                    )
                                    ->action(
                                        fn (Component $livewire) => $livewire->startMetadataAiGeneration()
                                    )
                            ),

                        ...DocumentClassificationFormFields::templateFields(),

                        Select::make('template_status_id')
                            ->relationship('templateStatus', 'name')
                            ->default(fn (): int => TemplateStatus::idFor(TemplateStatus::DRAFT))
                            ->required(),

                        Select::make('report_template_id')
                            ->label('Print & Report Template')
                            ->options(fn (): array => ReportTemplate::query()
                                ->active()
                                ->where('scope', ReportScope::ControlledDocument)
                                ->where('format', ReportFormat::Pdf)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->helperText('Default template used when printing or generating PDFs from this document template.')
                            ->nullable(),

                        TextInput::make('current_version')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),

                    ]),
                ])
                ->disabled(fn (?DocumentTemplate $record): bool => $record?->isGenerationInProgress() ?? false)
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
                    ->color(fn (DocumentTemplate $record): string => match ($record->templateStatus?->code) {
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
            ApprovalInstancesRelationManager::class,
            ApprovalEventsRelationManager::class,
            SectionRelationManager::class,
            VariableRelationManager::class,
            TemplateAuditRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentTemplates::route('/'),
            'create' => CreateDocumentTemplate::route('/create'),
            'view' => ViewDocumentTemplate::route('/{record}'),
            'edit' => EditDocumentTemplate::route('/{record}/edit'),
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
