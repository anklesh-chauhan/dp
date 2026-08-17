<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductRecalls\Schemas;

use App\Domain\QMS\Enums\ProductRecallType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductRecallForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product recall')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('description')->required()->rows(5)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        Select::make('type')->options(ProductRecallType::class)->required()->live(),
                        TextInput::make('product_name')->required()->maxLength(255)->live(onBlur: true),
                        TextInput::make('product_code')->maxLength(255)->live(onBlur: true),
                    ]),
                    TagsInput::make('batch_numbers')->placeholder('Add batch number')->columnSpanFull(),
                    Textarea::make('market_countries')->rows(2)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make('Responsibility')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('complaint_id')
                            ->relationship('complaint', 'complaint_number')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
