<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductLicenses\Pages;

use App\Filament\Resources\ProductLicenses\ProductLicenseResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewProductLicense extends ViewRecord
{
    protected static string $resource = ProductLicenseResource::class;
}
