<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\RelationManagers;

use App\Domain\QMS\Enums\CsvExecutionResult;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TestExecutionsRelationManager extends RelationManager
{
    protected static string $relationship = 'testExecutions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('csv_test_case_id')
                    ->relationship('testCase', 'test_identifier')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('execution_no')->numeric()->minValue(1)->required(),
                TextInput::make('environment')->required(),
                TextInput::make('application_version')->required(),
                TextInput::make('commit_sha'),
                TextInput::make('configuration_hash')->maxLength(64),
                Repeater::make('step_results')->schema([
                    TextInput::make('step')->required(),
                    Select::make('result')->options(['passed' => 'Passed', 'failed' => 'Failed', 'blocked' => 'Blocked'])->required(),
                    Textarea::make('actual_result')->required(),
                    TextInput::make('evidence_reference'),
                ])->columns(2)->minItems(1)->required()->columnSpanFull(),
                Select::make('result')->options(self::options(CsvExecutionResult::cases()))->required(),
                Textarea::make('actual_result')->required()->columnSpanFull(),
                Textarea::make('evidence_summary')->columnSpanFull(),
                Select::make('deviation_id')->relationship('deviation', 'deviation_number')->searchable()->preload(),
                Select::make('executed_by')->relationship('executor', 'name')->searchable()->preload()->required(),
                Select::make('reviewed_by')->relationship('reviewer', 'name')->searchable()->preload(),
                DateTimePicker::make('started_at')->required(),
                DateTimePicker::make('completed_at'),
                DateTimePicker::make('reviewed_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('execution_uuid')
            ->columns([
                TextColumn::make('testCase.test_identifier')->label('Test'),
                TextColumn::make('execution_no')->label('Run'),
                TextColumn::make('application_version')->label('Version'),
                TextColumn::make('result')->badge(),
                TextColumn::make('executor.name')->label('Executor'),
                TextColumn::make('reviewer.name')->label('Reviewer'),
                TextColumn::make('reviewed_at')->dateTime(),
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
