<?php

declare(strict_types=1);

namespace App\Filament\Resources\IssuanceStatuses\Pages;

use App\Filament\Resources\IssuanceStatuses\IssuanceStatusResource;
use App\Filament\Resources\LookupPages\ListLookupRecords;

class ListIssuanceStatuses extends ListLookupRecords
{
    protected static string $resource = IssuanceStatusResource::class;
}
