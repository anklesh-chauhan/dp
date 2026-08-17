<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductQualityReviews\Pages;

use App\Filament\Resources\ProductQualityReviews\ProductQualityReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListProductQualityReviews extends ListRecords
{
    protected static string $resource = ProductQualityReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
