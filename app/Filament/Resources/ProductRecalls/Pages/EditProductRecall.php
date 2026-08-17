<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductRecalls\Pages;

use App\Filament\Resources\ProductRecalls\ProductRecallResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditProductRecall extends EditRecord
{
    protected static string $resource = ProductRecallResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
