<?php

declare(strict_types=1);

namespace App\Filament\Resources\SupplierQualifications\Pages;

use App\Filament\Resources\SupplierQualifications\SupplierQualificationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSupplierQualifications extends ListRecords
{
    protected static string $resource = SupplierQualificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
