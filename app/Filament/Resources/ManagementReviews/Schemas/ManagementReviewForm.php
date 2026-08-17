<?php

declare(strict_types=1);

namespace App\Filament\Resources\ManagementReviews\Schemas;

use App\Domain\QMS\Enums\ManagementReviewInputType;
use App\Domain\QMS\Enums\ManagementReviewType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ManagementReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Management Review')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Select::make('type')->options(ManagementReviewType::class)->required()->live(),
                    Grid::make(3)->schema([
                        DatePicker::make('period_start_at')->required()->live(),
                        DatePicker::make('period_end_at')->required()->live(),
                        DateTimePicker::make('scheduled_at')->live(),
                    ]),
                    Grid::make(2)->schema([
                        Select::make('chair_id')
                            ->relationship('chair', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Select::make('coordinator_id')
                            ->relationship('coordinator', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    ]),
                    CheckboxList::make('required_inputs')
                        ->options(ManagementReviewInputType::class)
                        ->columns(2)
                        ->required()
                        ->default(array_map(
                            static fn (ManagementReviewInputType $type): string => $type->value,
                            ManagementReviewInputType::cases(),
                        ))
                        ->columnSpanFull(),
                    Textarea::make('input_summary')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('decisions')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('action_summary')->rows(4)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
