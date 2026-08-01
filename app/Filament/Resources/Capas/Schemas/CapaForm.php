<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas\Schemas;

use App\Domain\QMS\Enums\CapaType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class CapaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Corrective and Preventive Action')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        Select::make('deviation_id')
                            ->relationship('deviation', 'deviation_number')
                            ->searchable()
                            ->preload()
                            ->required()->live(),
                        Select::make('investigation_id')
                            ->relationship('investigation', 'investigation_number')
                            ->searchable()
                            ->preload()->live(),
                        Select::make('type')->options(CapaType::class)->required()->live(),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()->live(),
                        DatePicker::make('due_at')->required()->live(),
                        DatePicker::make('effectiveness_due_at')->live(),
                    ]),
                    Textarea::make('action_plan')->required()->rows(6)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('effectiveness_result')->rows(5)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
