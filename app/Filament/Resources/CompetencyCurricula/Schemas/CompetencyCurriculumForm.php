<?php

declare(strict_types=1);

namespace App\Filament\Resources\CompetencyCurricula\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CompetencyCurriculumForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Curriculum')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true)->live(onBlur: true),
                        TextInput::make('name')->required()->maxLength(255)->live(onBlur: true),
                        TextInput::make('role_name')->label('Role name')->maxLength(255)->live(onBlur: true),
                        Select::make('sop_role_id')
                            ->label('SOP role')
                            ->relationship('sopRole', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        TextInput::make('requalification_months')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(120)
                            ->live(onBlur: true),
                        TextInput::make('gate_key')
                            ->label('Gate key')
                            ->helperText('e.g. document_execution_qa or change_control_approve')
                            ->maxLength(100)
                            ->live(onBlur: true),
                        Toggle::make('is_active')->label('Active')->inline(false),
                    ]),
                    Textarea::make('description')->rows(3)->columnSpanFull()->live(onBlur: true),
                ])
                ->columnSpanFull(),
        ]);
    }
}
