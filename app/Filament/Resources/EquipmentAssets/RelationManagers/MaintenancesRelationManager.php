<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\RelationManagers;

use App\Domain\QMS\Enums\EquipmentMaintenanceStatus;
use App\Filament\Resources\EquipmentMaintenances\EquipmentMaintenanceResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class MaintenancesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenances';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('due_at')->required()->native(false),
            Textarea::make('description')->required()->rows(3),
            Textarea::make('notes')->rows(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('work_order_number')->label('Work Order')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('description')->limit(40),
                TextColumn::make('due_at')->dateTime()->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Create:EquipmentMaintenance'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = EquipmentMaintenanceStatus::Planned->value;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => EquipmentMaintenanceResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('id', 'desc');
    }
}
