<?php

declare(strict_types=1);

namespace App\Filament\Resources\TrainingPrograms\RelationManagers;

use App\Enums\ProductModule;
use App\Support\Modules\ModuleManager;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class RoleRequirementsRelationManager extends RelationManager
{
    protected static string $relationship = 'roleRequirements';

    protected static ?string $title = 'Role requirements';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::TMS)
            && (bool) auth()->user()?->can('View:TrainingProgram');
    }

    public function isReadOnly(): bool
    {
        return ! (bool) auth()->user()?->can('Update:TrainingProgram');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('designation_id')
                ->label('Designation')
                ->relationship('designation', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Toggle::make('is_required')->label('Required')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('designation.code')->label('Code')->searchable(),
                TextColumn::make('designation.name')->label('Designation')->searchable(),
                IconColumn::make('is_required')->boolean()->label('Required'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:TrainingProgram')),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:TrainingProgram')),
            ]);
    }
}
