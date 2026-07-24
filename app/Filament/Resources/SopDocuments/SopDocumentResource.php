<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopDocuments;

use App\Filament\Resources\SopDocuments\Pages\CreateSopDocument;
use App\Filament\Resources\SopDocuments\Pages\EditSopDocument;
use App\Filament\Resources\SopDocuments\Pages\ListSopDocuments;
use App\Filament\Resources\SopDocuments\Pages\ViewSopDocument;
use App\Filament\Resources\SopDocuments\RelationManagers\ApprovalRelationManager;
use App\Filament\Resources\SopDocuments\RelationManagers\AuditRelationManager;
use App\Filament\Resources\SopDocuments\RelationManagers\DocumentSectionRelationManager;
use App\Filament\Resources\SopDocuments\RelationManagers\DocumentVariableRelationManager;
use App\Filament\Support\DocumentClassificationFormFields;
use App\Filament\Support\TemplateVariableFieldBuilder;
use App\Models\DocumentStatus;
use App\Models\DocumentType;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SopDocumentResource extends Resource
{
    protected static ?string $model = SopDocument::class;

    protected static string|UnitEnum|null $navigationGroup = 'SOP Management';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'document_number';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return strval(static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Document Details')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('template_id')
                            ->relationship(
                                'template',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->whereHas('templateStatus', fn (Builder $statusQuery): Builder => $statusQuery->where('code', TemplateStatus::PUBLISHED))
                                    ->whereHas('publishedVersion')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                $versionId = self::publishedTemplateVersionId($state);

                                $set('template_version_id', $versionId);
                                $set('variables', self::templateVariableDefaultValues($versionId));
                                $set('regulationTags', DocumentClassificationFormFields::regulationTagIdsForTemplate($state));
                            }),
                        Select::make('template_version_id')
                            ->label('Template Version')
                            ->options(fn (Get $get): array => self::publishedTemplateVersionOptions((int) $get('template_id')))
                            ->searchable()
                            ->preload()
                            ->required(fn ($livewire): bool => $livewire instanceof CreateSopDocument)
                            ->disabled(fn (Get $get): bool => blank($get('template_id')))
                            ->live()
                            ->afterStateUpdated(fn (Set $set, ?int $state): mixed => $set('variables', self::templateVariableDefaultValues($state))),
                        ...DocumentClassificationFormFields::templateDerivedDisplayFields(),
                        Select::make('referenced_sop_document_id')
                            ->label('Referenced SOP')
                            ->relationship(
                                'referencedSop',
                                'document_number',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->whereHas(
                                        'documentType',
                                        fn (Builder $q) => $q->where('code', DocumentType::SOP)
                                    )
                            )
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => self::requiresSopReference($get)),

                        TextInput::make('document_number')
                            ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument))
                            ->required(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument)),

                        TextInput::make('title')->required()->maxLength(255),

                        TextInput::make('version')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument))
                            ->required(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument)),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument))
                            ->required(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument)),
                        Select::make('document_status_id')
                            ->relationship('documentStatus', 'name')
                            ->default(fn (): int => DocumentStatus::idFor(DocumentStatus::DRAFT))
                            ->visible(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument))
                            ->disabled(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument))
                            ->dehydrated(false)
                            ->required(fn ($livewire): bool => ! ($livewire instanceof CreateSopDocument)),
                        Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload()->required(),
                        DatePicker::make('effective_date'),
                        DatePicker::make('review_date'),
                    ]),
                ])
                ->columnSpanFull(),

            Section::make('Template Variable Values')
                ->key(fn (Get $get): string => 'template-variables-'.($get('template_version_id') ?? 'none'))
                ->schema(fn (Get $get): array => TemplateVariableFieldBuilder::fields(
                    (int) $get('template_version_id'),
                    (int) $get('template_id'),
                ))
                ->columns(2)
                ->visible(fn ($livewire, Get $get): bool => $livewire instanceof CreateSopDocument && filled($get('template_version_id')))
                ->dehydrated(fn ($livewire): bool => $livewire instanceof CreateSopDocument)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')->searchable()->sortable(),
                TextColumn::make('version')
                    ->label('Document Version')
                    ->sortable(),
                TextColumn::make('templateVersion.version')
                    ->label('Template Version')
                    ->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('documentType.category.name')
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
                TextColumn::make('department.name')->searchable(),
                TextColumn::make('template.code')->searchable(),
                TextColumn::make('documentStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (SopDocument $record): string => match ($record->documentStatus?->code) {
                        DocumentStatus::DRAFT => 'gray',
                        DocumentStatus::UNDER_REVIEW => 'warning',
                        DocumentStatus::APPROVED => 'info',
                        DocumentStatus::EFFECTIVE => 'success',
                        DocumentStatus::SUPERSEDED => 'warning',
                        DocumentStatus::OBSOLETE => 'warning',
                        DocumentStatus::ARCHIVED => 'gray',
                        DocumentStatus::RETENTION_COMPLETED => 'gray',
                        DocumentStatus::DESTROYED => 'danger',
                        DocumentStatus::REJECTED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('lockedByUser.name')
                    ->label('Locked By')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('effective_date')->date()->sortable(),
                TextColumn::make('review_date')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('document_type_id')
                    ->relationship('documentType', 'name')
                    ->label('Document Type'),
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                SelectFilter::make('document_status_id')->relationship('documentStatus', 'name')->label('Status'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('printPdf')
                    ->label('Print / PDF')
                    ->icon(Heroicon::Printer)
                    ->url(fn (SopDocument $record): string => route('sop-documents.print', $record))
                    ->openUrlInNewTab(),
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
            DocumentSectionRelationManager::class,
            DocumentVariableRelationManager::class,
            ApprovalRelationManager::class,
            AuditRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSopDocuments::route('/'),
            'create' => CreateSopDocument::route('/create'),
            'view' => ViewSopDocument::route('/{record}'),
            'edit' => EditSopDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['department', 'template.category', 'template.regulationTags', 'templateVersion', 'documentType.category', 'regulationTags', 'documentStatus', 'lockedByUser']);
    }

    private static function publishedTemplateVersionId(?int $templateId): ?int
    {
        if ($templateId === null) {
            return null;
        }

        return SopTemplateVersion::query()
            ->where('sop_template_id', $templateId)
            ->whereHas('templateStatus', fn (Builder $statusQuery): Builder => $statusQuery->where('code', TemplateStatus::PUBLISHED))
            ->latest('version')
            ->value('id');
    }

    /**
     * @return array<string, string>
     */
    private static function publishedTemplateVersionOptions(?int $templateId): array
    {
        if ($templateId === null || $templateId === 0) {
            return [];
        }

        return SopTemplateVersion::query()
            ->where('sop_template_id', $templateId)
            ->whereHas('templateStatus', fn (Builder $statusQuery): Builder => $statusQuery->where('code', TemplateStatus::PUBLISHED))
            ->orderByDesc('version')
            ->pluck('version', 'id')
            ->map(fn (int $version): string => "Version {$version}")
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function templateVariableDefaultValues(?int $templateVersionId): array
    {
        return TemplateVariableFieldBuilder::defaultValues($templateVersionId);
    }

    protected static function requiresSopReference(Get $get): bool
    {
        $templateId = $get('template_id');

        if (blank($templateId)) {
            return false;
        }

        return SopTemplate::query()
            ->with('documentType')
            ->find($templateId)
            ?->documentType
            ?->requiresSopReference() ?? false;
    }
}
