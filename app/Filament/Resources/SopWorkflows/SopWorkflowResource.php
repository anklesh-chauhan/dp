<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopWorkflows;

use App\Filament\Resources\SopWorkflows\Pages\CreateSopWorkflow;
use App\Filament\Resources\SopWorkflows\Pages\EditSopWorkflow;
use App\Filament\Resources\SopWorkflows\Pages\ListSopWorkflows;
use App\Filament\Resources\SopWorkflows\Pages\ViewSopWorkflow;
use App\Filament\Resources\SopWorkflows\RelationManagers\WorkflowRelationManager;
use App\Models\SopWorkflow;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SopWorkflowResource extends Resource
{
    protected static ?string $model = SopWorkflow::class;

    protected static string|UnitEnum|null $navigationGroup = 'SOP Management';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return strval(static::getModel()::count());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(255)->unique(ignoreRecord: true),
                Toggle::make('is_active')->default(true),
                Select::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Global (all departments)'),
                Textarea::make('description')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('department.name')->label('Department')->placeholder('Global'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('steps_count')->counts('steps')->label('Steps'),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            WorkflowRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSopWorkflows::route('/'),
            'create' => CreateSopWorkflow::route('/create'),
            'view' => ViewSopWorkflow::route('/{record}'),
            'edit' => EditSopWorkflow::route('/{record}/edit'),
        ];
    }
}
