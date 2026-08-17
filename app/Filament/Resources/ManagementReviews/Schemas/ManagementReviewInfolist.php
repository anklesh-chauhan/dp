<?php

declare(strict_types=1);

namespace App\Filament\Resources\ManagementReviews\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ManagementReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Management Review')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('review_number')->label('Review Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('period_start_at')->date(),
                        TextEntry::make('period_end_at')->date(),
                        TextEntry::make('scheduled_at')->dateTime()->placeholder('—'),
                        TextEntry::make('chair.name')->label('Chair')->placeholder('—'),
                        TextEntry::make('coordinator.name')->label('Coordinator')->placeholder('—'),
                        TextEntry::make('approver.name')->label('Approver')->placeholder('—'),
                        TextEntry::make('required_inputs')
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull(),
                        TextEntry::make('input_summary')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('decisions')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('action_summary')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('held_at')->dateTime()->placeholder('—'),
                        TextEntry::make('minutes_issued_at')->dateTime()->placeholder('—'),
                        TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                        TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }
}
