<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductQualityReviews\Pages;

use App\Filament\Resources\ProductQualityReviews\ProductQualityReviewResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditProductQualityReview extends EditRecord
{
    protected static string $resource = ProductQualityReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
