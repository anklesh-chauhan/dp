<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas\Pages;

use App\Filament\Resources\Capas\CapaResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCapa extends CreateRecord
{
    protected static string $resource = CapaResource::class;
}
