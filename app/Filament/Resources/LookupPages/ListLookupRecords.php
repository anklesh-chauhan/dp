<?php

declare(strict_types=1);

namespace App\Filament\Resources\LookupPages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLookupRecords extends ListRecords
{
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
