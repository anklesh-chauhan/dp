<?php

declare(strict_types=1);

namespace App\Filament\Resources\EquipmentAssets\RelationManagers;

use App\Domain\QMS\Enums\EquipmentQualificationStatus;
use App\Domain\QMS\Enums\EquipmentQualificationType;
use App\Filament\Resources\EquipmentQualifications\EquipmentQualificationResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class QualificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'qualifications';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->options(EquipmentQualificationType::class)->required(),
            TextInput::make('protocol_title')->required()->maxLength(255),
            Textarea::make('protocol_summary')->rows(3),
            Textarea::make('acceptance_criteria')->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('qualification_number')->label('Number')->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('protocol_title')->limit(40),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => (bool) auth()->user()?->can('Create:EquipmentQualification'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = EquipmentQualificationStatus::Draft->value;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record): string => EquipmentQualificationResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('id', 'desc');
    }
}
