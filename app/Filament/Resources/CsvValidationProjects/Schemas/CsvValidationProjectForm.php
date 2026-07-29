<?php

declare(strict_types=1);

namespace App\Filament\Resources\CsvValidationProjects\Schemas;

use App\Domain\QMS\Enums\CsvCriticality;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CsvValidationProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Validated System')
                    ->description('Define the system boundary and intended GxP use before planning tests.')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('system_identifier')->required()->maxLength(255),
                            TextInput::make('system_name')->required()->maxLength(255),
                            TextInput::make('system_version')->maxLength(255),
                            Select::make('gxp_criticality')
                                ->options(self::enumOptions(CsvCriticality::cases()))
                                ->required(),
                        ]),
                        Textarea::make('intended_use')->required()->rows(4)->columnSpanFull(),
                        TagsInput::make('regulatory_scope')
                            ->placeholder('21 CFR Part 11')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Ownership and Scope')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('department_id')->relationship('department', 'name')->searchable()->preload(),
                            Select::make('business_owner_id')->relationship('businessOwner', 'name')->searchable()->preload()->required(),
                            Select::make('system_owner_id')->relationship('systemOwner', 'name')->searchable()->preload()->required(),
                            Select::make('quality_owner_id')->relationship('qualityOwner', 'name')->searchable()->preload()->required(),
                            DatePicker::make('planned_release_date'),
                            DatePicker::make('next_periodic_review_date'),
                        ]),
                        Grid::make(3)->schema([
                            Checkbox::make('is_gxp')->label('GxP relevant'),
                            Checkbox::make('uses_electronic_records'),
                            Checkbox::make('uses_electronic_signatures'),
                        ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Validation Package')
                    ->description('These controlled documents and baselines become part of the release evidence.')
                    ->schema([
                        Select::make('validation_plan_document_id')
                            ->relationship('validationPlanDocument', 'document_number')
                            ->searchable()
                            ->preload(),
                        Select::make('summary_report_document_id')
                            ->relationship('summaryReportDocument', 'document_number')
                            ->searchable()
                            ->preload(),
                        Select::make('change_control_id')
                            ->relationship('changeControl', 'change_number')
                            ->searchable()
                            ->preload(),
                        Textarea::make('validation_strategy')->rows(5)->columnSpanFull(),
                        KeyValue::make('release_baseline')
                            ->keyLabel('Baseline item')
                            ->valueLabel('Approved value / version')
                            ->columnSpanFull(),
                        Textarea::make('validation_summary')->rows(5)->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  array<int, \BackedEnum>  $cases
     * @return array<string, string>
     */
    private static function enumOptions(array $cases): array
    {
        return collect($cases)->mapWithKeys(
            fn (\BackedEnum $case): array => [$case->value => str($case->value)->replace('_', ' ')->title()->toString()],
        )->all();
    }
}
