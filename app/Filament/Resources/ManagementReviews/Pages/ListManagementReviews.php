<?php

declare(strict_types=1);

namespace App\Filament\Resources\ManagementReviews\Pages;

use App\Filament\Resources\ManagementReviews\ManagementReviewResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListManagementReviews extends ListRecords
{
    protected static string $resource = ManagementReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
