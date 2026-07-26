<?php

declare(strict_types=1);

namespace App\Filament\Resources\Investigations\Pages;

use App\Filament\Resources\Investigations\InvestigationResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateInvestigation extends CreateRecord
{
    protected static string $resource = InvestigationResource::class;
}
