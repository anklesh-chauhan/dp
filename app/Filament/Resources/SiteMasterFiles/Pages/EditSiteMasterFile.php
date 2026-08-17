<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteMasterFiles\Pages;

use App\Filament\Resources\SiteMasterFiles\SiteMasterFileResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditSiteMasterFile extends EditRecord
{
    protected static string $resource = SiteMasterFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
