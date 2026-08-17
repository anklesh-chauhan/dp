<?php

declare(strict_types=1);

namespace App\Filament\Resources\Complaints\Schemas;

use App\Domain\QMS\Enums\ComplaintSource;
use App\Domain\QMS\Enums\ComplaintType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ComplaintForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Complaint')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('description')->required()->rows(5)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        Select::make('source')->options(ComplaintSource::class)->required()->live(),
                        Select::make('type')->options(ComplaintType::class)->required()->live(),
                        DateTimePicker::make('received_at')->required()->live(),
                    ]),
                    TextInput::make('external_reference')->maxLength(255)->live(onBlur: true),
                ])
                ->columnSpanFull(),
            Section::make('Responsibility')
                ->schema([
                    Grid::make(2)->schema([
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
                    ]),
                    DatePicker::make('response_due_at')->live(),
                ])
                ->columnSpanFull(),
            Section::make('Product & regulatory')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('product_name')->maxLength(255)->live(onBlur: true),
                        TextInput::make('batch_number')->maxLength(255)->live(onBlur: true),
                        TextInput::make('market_country_code')->maxLength(2)->live(onBlur: true),
                    ]),
                    Grid::make(2)->schema([
                        Toggle::make('adverse_event_suspected')->inline(false)->live(),
                        Toggle::make('regulatory_reportable')->inline(false)->live(),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('regulatory_authority')->maxLength(255)->live(onBlur: true),
                        DatePicker::make('regulatory_report_due_at')->live(),
                        DateTimePicker::make('regulatory_reported_at')->live(),
                    ]),
                    TextInput::make('regulatory_reference')->maxLength(255)->live(onBlur: true),
                ])
                ->columnSpanFull(),
        ]);
    }
}
