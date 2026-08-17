<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductQualityReviews\Schemas;

use App\Domain\QMS\Enums\ProductQualityReviewType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductQualityReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Quality Review')
                ->schema([
                    TextInput::make('title')->required()->maxLength(255)->live(onBlur: true)->columnSpanFull(),
                    Select::make('type')->options(ProductQualityReviewType::class)->required()->live(),
                    Grid::make(3)->schema([
                        TextInput::make('product_name')->required()->maxLength(255)->live(onBlur: true),
                        TextInput::make('product_code')->maxLength(255)->live(onBlur: true),
                        TextInput::make('dosage_form')->maxLength(255)->live(onBlur: true),
                    ]),
                    Grid::make(3)->schema([
                        DatePicker::make('period_start_at')->required()->live(),
                        DatePicker::make('period_end_at')->required()->live(),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    ]),
                    Textarea::make('input_summary')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('conclusions')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('recommendations')->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('yield_summary')->rows(3)->live(onBlur: true)->columnSpanFull(),
                    Textarea::make('reject_summary')->rows(3)->live(onBlur: true)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
