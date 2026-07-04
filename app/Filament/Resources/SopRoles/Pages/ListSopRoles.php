<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopRoles\Pages;

use App\Filament\Resources\LookupPages\ListLookupRecords;
use App\Filament\Resources\SopRoles\SopRoleResource;

class ListSopRoles extends ListLookupRecords
{
    protected static string $resource = SopRoleResource::class;
}
