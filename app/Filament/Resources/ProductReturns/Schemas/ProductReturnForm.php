<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductReturns\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product return')
                ->schema([
                    Textarea::make('reason')->required()->rows(4)->live(onBlur: true)->columnSpanFull(),
                    Grid::make(3)->schema([
                        TextInput::make('product_name')->required()->maxLength(255)->live(onBlur: true),
                        TextInput::make('batch_number')->maxLength(255)->live(onBlur: true),
                        TextInput::make('source')->maxLength(255)->live(onBlur: true),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('quantity')->numeric()->live(onBlur: true),
                        TextInput::make('unit')->maxLength(50)->live(onBlur: true),
                    ]),
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
                        Select::make('product_recall_id')
                            ->relationship('productRecall', 'recall_number')
                            ->searchable()
                            ->preload()
                            ->live(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
