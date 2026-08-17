<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\RelationManagers;

use App\Domain\QMS\Enums\EquipmentCalibrationStatus;
use App\Filament\Resources\EquipmentCalibrations\EquipmentCalibrationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class CalibrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'calibrations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('due_at')->required()->native(false),
            TextInput::make('certificate_reference')->maxLength(255),
            Textarea::make('notes')->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('calibration_number')->label('Number')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('result')->badge(),
                TextColumn::make('due_at')->dateTime()->placeholder('—'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Create:EquipmentCalibration'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = EquipmentCalibrationStatus::Scheduled->value;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => EquipmentCalibrationResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('id', 'desc');
    }
}
