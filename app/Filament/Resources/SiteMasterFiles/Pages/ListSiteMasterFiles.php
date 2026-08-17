<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles\Pages;

use App\Filament\Resources\SiteMasterFiles\SiteMasterFileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListSiteMasterFiles extends ListRecords
{
    protected static string $resource = SiteMasterFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
