<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\RelationManagers;

use App\Enums\ProductModule;
use App\Support\Modules\ModuleManager;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Required documents';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return app(ModuleManager::class)->enabled(ProductModule::QMS)
            && (bool) auth()->user()?->can('View:CompetencyCurriculum');
    }

    public function isReadOnly(): bool
    {
        return ! (bool) auth()->user()?->can('Update:CompetencyCurriculum');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('controlled_document_id')
                ->label('Controlled document')
                ->relationship('controlledDocument', 'document_number')
                ->searchable()
                ->preload()
                ->required(),
            Toggle::make('is_required')->label('Required')->default(true),
            TextInput::make('sort_order')->numeric()->default(0)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('controlledDocument.document_number')->label('Document')->searchable(),
                TextColumn::make('controlledDocument.title')->label('Title')->limit(40)->placeholder('—'),
                IconColumn::make('is_required')->boolean()->label('Required'),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:CompetencyCurriculum')),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:CompetencyCurriculum')),
                DeleteAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Update:CompetencyCurriculum')),
            ]);
    }
}
