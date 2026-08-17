<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductReturns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductReturnInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product return')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('return_number')->label('Return Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('disposition')->badge(),
                        TextEntry::make('product_name'),
                        TextEntry::make('batch_number')->placeholder('—'),
                        TextEntry::make('source')->placeholder('—'),
                        TextEntry::make('quantity')->placeholder('—'),
                        TextEntry::make('unit')->placeholder('—'),
                        TextEntry::make('reason')->columnSpanFull(),
                        TextEntry::make('productRecall.recall_number')->label('Linked Recall')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('creator.name')->label('Created By')->placeholder('—'),
                        TextEntry::make('received_at')->dateTime()->placeholder('—'),
                        TextEntry::make('quarantined_at')->dateTime()->placeholder('—'),
                        TextEntry::make('dispositioned_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('qa_disposition_notes')->placeholder('—')->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
