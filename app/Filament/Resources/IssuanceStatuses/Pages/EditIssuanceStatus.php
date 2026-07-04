<?php

declare(strict_types=1);

namespace App\Filament\Resources\IssuanceStatuses\Pages;

use App\Filament\Resources\IssuanceStatuses\IssuanceStatusResource;
use App\Filament\Resources\LookupPages\EditLookupRecord;

class EditIssuanceStatus extends EditLookupRecord
{
    protected static string $resource = IssuanceStatusResource::class;
}
