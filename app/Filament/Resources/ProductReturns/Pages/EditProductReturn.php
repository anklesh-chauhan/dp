<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductReturns\Pages;

use App\Filament\Resources\ProductReturns\ProductReturnResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditProductReturn extends EditRecord
{
    protected static string $resource = ProductReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
