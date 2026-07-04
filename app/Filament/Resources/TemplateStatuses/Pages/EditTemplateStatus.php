<?php

declare(strict_types=1);

namespace App\Filament\Resources\TemplateStatuses\Pages;

use App\Filament\Resources\LookupPages\EditLookupRecord;
use App\Filament\Resources\TemplateStatuses\TemplateStatusResource;

class EditTemplateStatus extends EditLookupRecord
{
    protected static string $resource = TemplateStatusResource::class;
}
