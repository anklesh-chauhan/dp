<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class RequirementsRelationManager extends RelationManager
{
    protected static string $relationship = 'requirements';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('requirement_identifier')
                    ->required()
                    ->maxLength(255),
                TextInput::make('version')->numeric()->default(1)->required(),
                TextInput::make('category')->required(),
                Textarea::make('statement')->required()->columnSpanFull(),
                Textarea::make('acceptance_criteria')->required()->columnSpanFull(),
                Textarea::make('rationale')->columnSpanFull(),
                TextInput::make('source_reference'),
                Select::make('criticality')->options(self::options(CsvCriticality::cases()))->required(),
                Select::make('status')->options(self::options(CsvRequirementStatus::cases()))->default('draft')->required(),
                Select::make('owner_id')->relationship('owner', 'name')->searchable()->preload(),
                Checkbox::make('gxp_relevant')->default(true),
                Checkbox::make('data_integrity_relevant'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('requirement_identifier')
            ->columns([
                TextColumn::make('requirement_identifier')
                    ->searchable(),
                TextColumn::make('version'),
                TextColumn::make('category')->badge(),
                TextColumn::make('criticality')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('test_cases_count')->counts('testCases')->label('Tests'),
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
