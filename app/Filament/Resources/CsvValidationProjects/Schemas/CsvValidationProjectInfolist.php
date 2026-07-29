<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CsvValidationProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Validation Status')->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('project_number'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('gxp_criticality')->badge(),
                        TextEntry::make('system_identifier'),
                        TextEntry::make('system_name'),
                        TextEntry::make('system_version')->placeholder('Not set'),
                    ]),
                    TextEntry::make('intended_use')->columnSpanFull(),
                ])->columnSpanFull(),
                Section::make('Release Readiness')->schema([
                    Grid::make(4)->schema([
                        TextEntry::make('requirements_count')->state(fn ($record): int => $record->requirements()->count())->label('Requirements'),
                        TextEntry::make('risks_count')->state(fn ($record): int => $record->risks()->count())->label('Risks'),
                        TextEntry::make('test_cases_count')->state(fn ($record): int => $record->testCases()->count())->label('Test cases'),
                        TextEntry::make('test_executions_count')->state(fn ($record): int => $record->testExecutions()->count())->label('Executions'),
                        IconEntry::make('validation_strategy_complete')->state(fn ($record): bool => filled($record->validation_strategy))->boolean()->label('Strategy'),
                        IconEntry::make('baseline_complete')->state(fn ($record): bool => filled($record->release_baseline))->boolean()->label('Baseline'),
                        IconEntry::make('summary_complete')->state(fn ($record): bool => filled($record->validation_summary))->boolean()->label('Summary'),
                        TextEntry::make('next_periodic_review_date')->date()->placeholder('Not scheduled'),
                    ]),
                ])->columnSpanFull(),
                Section::make('Signed Release')->schema([
                    TextEntry::make('releaser.name')->label('Released by')->placeholder('Not released'),
                    TextEntry::make('released_at')->dateTime()->placeholder('Not released'),
                    TextEntry::make('validation_summary')->placeholder('Not completed')->columnSpanFull(),
                ])->columnSpanFull(),
            ]);
    }
}
