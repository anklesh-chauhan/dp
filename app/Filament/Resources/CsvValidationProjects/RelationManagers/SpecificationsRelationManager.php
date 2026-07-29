<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvSpecificationType;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class SpecificationsRelationManager extends RelationManager
{
    protected static string $relationship = 'specifications';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('specification_identifier')
                    ->required()
                    ->maxLength(255),
                TextInput::make('version')->numeric()->default(1)->required(),
                Select::make('type')->options(self::options(CsvSpecificationType::cases()))->required(),
                TextInput::make('title')->required(),
                Textarea::make('description')->required()->columnSpanFull(),
                Select::make('status')->options(self::options(CsvRequirementStatus::cases()))->default('draft')->required(),
                Select::make('controlled_document_id')->relationship('controlledDocument', 'document_number')->searchable()->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('specification_identifier')
            ->columns([
                TextColumn::make('specification_identifier')
                    ->searchable(),
                TextColumn::make('version'),
                TextColumn::make('type')->badge(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    private static function options(array $cases): array
    {
        return collect($cases)->mapWithKeys(fn (\BackedEnum $case): array => [
            $case->value => str($case->value)->replace('_', ' ')->title()->toString(),
        ])->all();
    }
}
