<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemplateStatuses\Pages;

use App\Filament\Resources\LookupPages\CreateLookupRecord;
use App\Filament\Resources\TemplateStatuses\TemplateStatusResource;

class CreateTemplateStatus extends CreateLookupRecord
{
    protected static string $resource = TemplateStatusResource::class;
}
