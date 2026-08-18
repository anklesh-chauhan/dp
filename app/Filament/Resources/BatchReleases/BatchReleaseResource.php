<?php

declare(strict_types=1);

namespace App\Filament\Resources\BatchReleases;

use App\Domain\QMS\Enums\BatchReleaseStatus;
use App\Domain\QMS\Models\BatchRelease;
use App\Enums\ProductModule;
use App\Filament\Resources\BatchReleases\Pages\CreateBatchRelease;
use App\Filament\Resources\BatchReleases\Pages\ListBatchReleases;
use App\Filament\Resources\BatchReleases\Pages\ViewBatchRelease;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class BatchReleaseResource extends Resource
{
    protected static ?string $model = BatchRelease::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 26;

    protected static ?string $navigationLabel = 'Batch Release';

    protected static ?string $recordTitleAttribute = 'release_number';

    protected static string|array $routeMiddleware = ['module:qms'];

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS) && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('batch_number')->required()->maxLength(100),
            TextInput::make('product_name')->required()->maxLength(255),
            Select::make('document_execution_id')->relationship('documentExecution', 'id')->searchable()->preload(),
            Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload()->required(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('release_number'),
            TextEntry::make('batch_number'),
            TextEntry::make('product_name'),
            TextEntry::make('status')->badge(),
            TextEntry::make('owner.name'),
            TextEntry::make('disposition_rationale')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('release_number')->searchable()->sortable(),
                TextColumn::make('batch_number')->searchable(),
                TextColumn::make('product_name')->limit(30),
                TextColumn::make('status')->badge(),
                TextColumn::make('owner.name'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBatchReleases::route('/'),
            'create' => CreateBatchRelease::route('/create'),
            'view' => ViewBatchRelease::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:BatchRelease');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:BatchRelease');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof BatchRelease
            && $record->status === BatchReleaseStatus::Draft
            && (bool) auth()->user()?->can('Update:BatchRelease');
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }
}
