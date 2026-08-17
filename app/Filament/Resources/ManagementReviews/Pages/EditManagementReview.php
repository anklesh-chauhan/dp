<?php

declare(strict_types=1);

namespace App\Filament\Resources\ManagementReviews\Pages;

use App\Filament\Resources\ManagementReviews\ManagementReviewResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditManagementReview extends EditRecord
{
    protected static string $resource = ManagementReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
