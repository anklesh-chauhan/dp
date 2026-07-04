<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopRoles\Pages;

use App\Filament\Resources\LookupPages\EditLookupRecord;
use App\Filament\Resources\SopRoles\SopRoleResource;

class EditSopRole extends EditLookupRecord
{
    protected static string $resource = SopRoleResource::class;
}
