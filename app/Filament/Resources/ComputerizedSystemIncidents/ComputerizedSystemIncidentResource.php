<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComputerizedSystemIncidents;

use App\Domain\QMS\Enums\ComputerizedSystemIncidentSeverity;
use App\Domain\QMS\Enums\ComputerizedSystemIncidentStatus;
use App\Domain\QMS\Models\ComputerizedSystemIncident;
use App\Enums\ProductModule;
use App\Filament\Resources\ComputerizedSystemIncidents\Pages\CreateComputerizedSystemIncident;
use App\Filament\Resources\ComputerizedSystemIncidents\Pages\ListComputerizedSystemIncidents;
use App\Filament\Resources\ComputerizedSystemIncidents\Pages\ViewComputerizedSystemIncident;
use App\Support\Modules\ModuleManager;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

final class ComputerizedSystemIncidentResource extends Resource
{
    protected static ?string $model = ComputerizedSystemIncident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'QMS';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = 'IT / CSV Incidents';

    protected static ?string $modelLabel = 'Computerized system incident';

    protected static ?string $recordTitleAttribute = 'incident_number';

    protected static string|array $routeMiddleware = ['module:qms'];

    public static function canAccess(): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS) && parent::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            Select::make('severity')->options(ComputerizedSystemIncidentSeverity::class)->required(),
            TextInput::make('category')->required()->default('availability'),
            Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload()->required(),
            Textarea::make('description')->required()->rows(4)->columnSpanFull(),
            Textarea::make('impact')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('incident_number'),
            TextEntry::make('title'),
            TextEntry::make('status')->badge(),
            TextEntry::make('severity')->badge(),
            TextEntry::make('category'),
            TextEntry::make('owner.name'),
            TextEntry::make('description')->columnSpanFull(),
            TextEntry::make('impact')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('incident_number')->searchable()->sortable(),
                TextColumn::make('title')->limit(40)->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('severity')->badge(),
                TextColumn::make('owner.name'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComputerizedSystemIncidents::route('/'),
            'create' => CreateComputerizedSystemIncident::route('/create'),
            'view' => ViewComputerizedSystemIncident::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('ViewAny:ComputerizedSystemIncident');
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('Create:ComputerizedSystemIncident');
    }

    public static function canEdit(mixed $record): bool
    {
        return $record instanceof ComputerizedSystemIncident
            && $record->status === ComputerizedSystemIncidentStatus::Open
            && (bool) auth()->user()?->can('Update:ComputerizedSystemIncident');
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }
}
