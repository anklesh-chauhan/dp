<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopRoles\Pages;

use App\Filament\Resources\LookupPages\CreateLookupRecord;
use App\Filament\Resources\SopRoles\SopRoleResource;

class CreateSopRole extends CreateLookupRecord
{
    protected static string $resource = SopRoleResource::class;
}
