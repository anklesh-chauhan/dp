<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvTestType;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TestCasesRelationManager extends RelationManager
{
    protected static string $relationship = 'testCases';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('test_identifier')
                    ->required()
                    ->maxLength(255),
                TextInput::make('version')->numeric()->default(1)->required(),
                Select::make('type')->options(self::options(CsvTestType::cases()))->required(),
                TextInput::make('title')->required(),
                Textarea::make('objective')->required(),
                Textarea::make('preconditions'),
                Textarea::make('test_data'),
                Repeater::make('steps')->schema([
                    TextInput::make('step')->required(),
                    Textarea::make('expected_result')->required(),
                ])->columns(2)->minItems(1)->required()->columnSpanFull(),
                Select::make('criticality')->options(self::options(CsvCriticality::cases()))->required(),
                Select::make('status')->options(self::options(CsvRequirementStatus::cases()))->default('draft')->required(),
                Select::make('requirements')
                    ->relationship('requirements', 'requirement_identifier')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('test_identifier')
            ->columns([
                TextColumn::make('test_identifier')
                    ->searchable(),
                TextColumn::make('version'),
                TextColumn::make('type')->badge(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('requirements_count')->counts('requirements')->label('Requirements'),
                TextColumn::make('executions_count')->counts('executions')->label('Runs'),
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
