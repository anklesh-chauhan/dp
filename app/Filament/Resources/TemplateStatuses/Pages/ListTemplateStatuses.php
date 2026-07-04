<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemplateStatuses\Pages;

use App\Filament\Resources\LookupPages\ListLookupRecords;
use App\Filament\Resources\TemplateStatuses\TemplateStatusResource;

class ListTemplateStatuses extends ListLookupRecords
{
    protected static string $resource = TemplateStatusResource::class;
}
