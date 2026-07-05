<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates;

use App\Filament\Resources\SopTemplates\Pages\CreateSopTemplate;
use App\Filament\Resources\SopTemplates\Pages\EditSopTemplate;
use App\Filament\Resources\SopTemplates\Pages\ListSopTemplates;
use App\Filament\Resources\SopTemplates\Pages\ViewSopTemplate;
use App\Filament\Resources\SopTemplates\RelationManagers\SectionRelationManager;
use App\Filament\Resources\SopTemplates\RelationManagers\TemplateAuditRelationManager;
use App\Filament\Resources\SopTemplates\RelationManagers\VariableRelationManager;
use App\Filament\Resources\SopTemplates\RelationManagers\VersionRelationManager;
use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class SopTemplateResource extends Resource
{
    protected static ?string $model = SopTemplate::class;

    protected static string|UnitEnum|null $navigationGroup = 'SOP Management';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Template Details')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('document_type_id')
                            ->relationship('documentType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('template_status_id')
                            ->relationship('templateStatus', 'name')
                            ->default(fn (): int => TemplateStatus::idFor(TemplateStatus::DRAFT))
                            ->required(),
                        TextInput::make('current_version')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),
                ])
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
            ->with(['department', 'category', 'documentType', 'templateStatus']);
    }
}
