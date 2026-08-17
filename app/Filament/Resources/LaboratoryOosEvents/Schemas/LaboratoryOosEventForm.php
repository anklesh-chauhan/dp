<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOosEvents\Schemas;

use App\Domain\QMS\Enums\LaboratoryOosPhaseOutcome;
use App\Domain\QMS\Enums\LaboratoryOosType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class LaboratoryOosEventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laboratory event')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        Select::make('type')->options(LaboratoryOosType::class)->required()->live(),
                        TextInput::make('test_name')->required()->maxLength(255)->live(onBlur: true),
                        TextInput::make('method_reference')->maxLength(255)->live(onBlur: true),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('sample_id')->maxLength(255)->live(onBlur: true),
                        TextInput::make('batch_number')->maxLength(255)->live(onBlur: true),
                        TextInput::make('product_name')->maxLength(255)->live(onBlur: true),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('specification_limit')->maxLength(255)->live(onBlur: true),
                        TextInput::make('observed_result')->maxLength(255)->live(onBlur: true),
                        TextInput::make('unit')->maxLength(50)->live(onBlur: true),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Responsibility')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('analyst_id')
                            ->relationship('analyst', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Investigation phases')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('phase_one_outcome')->options(LaboratoryOosPhaseOutcome::class)->live(),
                        Select::make('phase_two_outcome')->options(LaboratoryOosPhaseOutcome::class)->live(),
                    ]),
                    Textarea::make('phase_one_notes')->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('phase_two_notes')->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('hypothesis')->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('invalidation_justification')->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(2)->schema([
                        Select::make('investigation_id')
                            ->relationship('investigation', 'investigation_number')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('deviation_id')
                            ->relationship('deviation', 'deviation_number')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
