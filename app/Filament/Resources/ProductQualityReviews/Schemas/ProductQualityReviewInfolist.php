<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductQualityReviews\Schemas;

use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\QMS\Services\ProductQualityReviewTransitionService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ProductQualityReviewInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Product Quality Review')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('review_number')->label('Review Number')->copyable(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('product_name'),
                        TextEntry::make('product_code')->placeholder('—'),
                        TextEntry::make('dosage_form')->placeholder('—'),
                        TextEntry::make('period_start_at')->date(),
                        TextEntry::make('period_end_at')->date(),
                        TextEntry::make('owner.name')->label('Owner')->placeholder('—'),
                        TextEntry::make('approver.name')->label('Approver')->placeholder('—'),
                        TextEntry::make('input_summary')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('conclusions')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('recommendations')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('yield_summary')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('reject_summary')->columnSpanFull()->placeholder('—'),
                        TextEntry::make('started_at')->dateTime()->placeholder('—'),
                        TextEntry::make('approved_at')->dateTime()->placeholder('—'),
                        TextEntry::make('closed_at')->dateTime()->placeholder('—'),
                    ]),
                ])
                ->columnSpanFull(),
            Section::make('Period input counts (best-effort)')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('period_deviation_count')
                            ->label('Deviations')
                            ->state(fn (ProductQualityReview $record): int => self::periodCounts($record)['deviations']),
                        TextEntry::make('period_complaint_count')
                            ->label('Complaints')
                            ->state(fn (ProductQualityReview $record): int => self::periodCounts($record)['complaints']),
                        TextEntry::make('period_change_control_count')
                            ->label('Change Controls')
                            ->state(fn (ProductQualityReview $record): int => self::periodCounts($record)['change_controls']),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array{deviations: int, complaints: int, change_controls: int}
     */
    private static function periodCounts(ProductQualityReview $record): array
    {
        return app(ProductQualityReviewTransitionService::class)->periodInputCounts($record);
    }
}
