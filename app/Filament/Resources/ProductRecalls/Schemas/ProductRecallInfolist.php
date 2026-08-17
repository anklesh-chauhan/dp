<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductRecalls\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductRecallInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product recall')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('recall_number')->label('Recall Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('classification')->badge(),
                        IconEntry::make('is_mock')->boolean()->label('Mock / simulated'),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('description')->columnSpanFull(),
                        TextEntry::make('product_name'),
                        TextEntry::make('product_code')->placeholder('—'),
                        TextEntry::make('batch_numbers')
                            ->formatStateUsing(fn (mixed $state): string => is_array($state) ? implode(', ', $state) : (string) ($state ?? '—'))
                            ->placeholder('—'),
                        TextEntry::make('market_countries')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('complaint.complaint_number')->label('Complaint')->placeholder('—'),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('creator.name')->label('Created By')->placeholder('—'),
                        TextEntry::make('initiated_at')->dateTime()->placeholder('—'),
                        TextEntry::make('classified_at')->dateTime()->placeholder('—'),
                        TextEntry::make('notified_at')->dateTime()->placeholder('—'),
                        TextEntry::make('executed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('effectiveness_verified_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                        TextEntry::make('effectiveness_summary')->placeholder('—')->columnSpanFull(),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
