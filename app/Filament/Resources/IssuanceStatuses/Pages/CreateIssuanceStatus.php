<?php

declare(strict_types=1);

namespace App\Filament\Resources\IssuanceStatuses\Pages;

use App\Filament\Resources\IssuanceStatuses\IssuanceStatusResource;
use App\Filament\Resources\LookupPages\CreateLookupRecord;

class CreateIssuanceStatus extends CreateLookupRecord
{
    protected static string $resource = IssuanceStatusResource::class;
}
