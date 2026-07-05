<?php

declare(strict_types=1);

namespace App\Filament\Resources\LogDocuments;

use App\Filament\Resources\LogDocuments\Pages\CreateLogDocument;
use App\Filament\Resources\LogDocuments\Pages\EditLogDocument;
use App\Filament\Resources\LogDocuments\Pages\ListLogDocuments;
use App\Filament\Resources\LogDocuments\Pages\ViewLogDocument;
use App\Filament\Resources\LogDocuments\RelationManagers\IssuanceRelationManager;
use App\Filament\Resources\SopDocuments\RelationManagers\ApprovalRelationManager;
use App\Filament\Resources\SopDocuments\RelationManagers\AuditRelationManager;
use App\Filament\Resources\SopDocuments\RelationManagers\DocumentSectionRelationManager;
use App\Filament\Resources\SopDocuments\RelationManagers\DocumentVariableRelationManager;
use App\Filament\Support\TemplateVariableFieldBuilder;
use App\Models\DocumentType;
use App\Models\SopDocument;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use App\Services\Sop\SopReferenceService;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LogDocumentResource extends Resource
{
    /**
     * @var list<string>
     */
    private const LOG_FORM_VARIABLE_EXCLUSIONS = [
        'department',
        'batch_number',
        'product_name',
        'referenced_sop',
    ];

    protected static ?string $model = SopDocument::class;

    protected static ?string $navigationLabel = 'Log Documents';

    protected static ?string $modelLabel = 'Log Document';

    protected static ?string $pluralModelLabel = 'Log Documents';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = 'Document Issuance';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static ?string $recordTitleAttribute = 'document_number';

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
                            ->label('Template')
                            ->relationship(
                                'template',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->whereHas('templateStatus', fn (Builder $statusQuery): Builder => $statusQuery->where('code', TemplateStatus::PUBLISHED))
                                    ->whereHas('publishedVersion')
                                    ->whereHas('documentType', fn (Builder $typeQuery): Builder => $typeQuery->whereIn(
                                        'code',
                                        [DocumentType::LOG, DocumentType::BATCH_RECORD, DocumentType::FORM],
                                    ))
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                $versionId = self::publishedTemplateVersionId($state);

                                $set('template_version_id', $versionId);
                                $set('variables', self::templateVariableDefaultValues($versionId));
                            }),
                        Select::make('template_version_id')
                            ->label('Template Version')
                            ->options(fn (Get $get): array => self::publishedTemplateVersionOptions((int) $get('template_id')))
                            ->searchable()
                            ->preload()
                            ->required(fn ($livewire): bool => $livewire instanceof CreateLogDocument)
                            ->disabled(fn (Get $get): bool => blank($get('template_id')))
                            ->live()
                            ->afterStateUpdated(fn (Set $set, ?int $state): mixed => $set('variables', self::templateVariableDefaultValues($state))),
                        Select::make('referenced_sop_document_id')
                            ->label('Referenced Effective SOP')
                            ->options(fn (Get $get): array => app(SopReferenceService::class)->effectiveSopOptions((int) $get('template_id')))
                            ->searchable()
                            ->preload()
                            ->required(fn ($livewire): bool => $livewire instanceof CreateLogDocument)
                            ->helperText('Log documents must reference an effective SOP from the same department.'),
                        TextInput::make('title')->required()->maxLength(255),
                        Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload()->required(),
                        TextInput::make('batch_number')->label('Batch Number')->maxLength(100),
                        TextInput::make('product_name')->label('Product Name')->maxLength(255),
                        Textarea::make('purpose')->rows(2)->columnSpanFull(),
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
                    self::LOG_FORM_VARIABLE_EXCLUSIONS,
                ))
                ->columns(2)
                ->visible(fn ($livewire, Get $get): bool => $livewire instanceof CreateLogDocument
                    && filled($get('template_version_id'))
                    && TemplateVariableFieldBuilder::fields(
                        (int) $get('template_version_id'),
                        (int) $get('template_id'),
                        self::LOG_FORM_VARIABLE_EXCLUSIONS,
                    ) !== [])
                ->dehydrated(fn ($livewire): bool => $livewire instanceof CreateLogDocument)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')->searchable()->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('documentType.name')->label('Type'),
                TextColumn::make('referenced_sop_number')->label('Referenced SOP')->searchable(),
                TextColumn::make('department.name')->searchable(),
                TextColumn::make('batch_number')->toggleable(),
                TextColumn::make('documentStatus.name')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('activeIssuances_count')->counts('activeIssuances')->label('Active Copies'),
            ])
            ->filters([
                SelectFilter::make('document_status_id')->relationship('documentStatus', 'name')->label('Status'),
                SelectFilter::make('document_type_id')->relationship('documentType', 'name')->label('Type'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DocumentSectionRelationManager::class,
            DocumentVariableRelationManager::class,
            ApprovalRelationManager::class,
            IssuanceRelationManager::class,
            AuditRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLogDocuments::route('/'),
            'create' => CreateLogDocument::route('/create'),
            'view' => ViewLogDocument::route('/{record}'),
            'edit' => EditLogDocument::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->logDocuments()
            ->with(['department', 'documentType', 'documentStatus', 'referencedSop', 'lockedByUser']);
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user !== null && ($user->can('ViewAny:LogDocument') || $user->can('ViewAny:SopDocument'));
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user !== null && ($user->can('Create:LogDocument') || $user->can('Create:SopDocument'));
    }

    public static function canView($record): bool
    {
        $user = Auth::user();

        return $user !== null && ($user->can('View:LogDocument') || $user->can('View:SopDocument'));
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        if ($user === null) {
            return false;
        }

        if (! ($user->can('Update:LogDocument') || $user->can('Update:SopDocument'))) {
            return false;
        }

        return $record instanceof SopDocument && $record->canBeEditedBy($user);
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
     * @return array<string, mixed>
     */
    private static function templateVariableDefaultValues(?int $templateVersionId): array
    {
        return TemplateVariableFieldBuilder::defaultValues($templateVersionId, self::LOG_FORM_VARIABLE_EXCLUSIONS);
    }
}
