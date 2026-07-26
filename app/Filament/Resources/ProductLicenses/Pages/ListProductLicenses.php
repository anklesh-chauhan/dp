<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductLicenses\Pages;

use App\Filament\Resources\ProductLicenses\ProductLicenseResource;
use Filament\Resources\Pages\ListRecords;

final class ListProductLicenses extends ListRecords
{
    protected static string $resource = ProductLicenseResource::class;
}
