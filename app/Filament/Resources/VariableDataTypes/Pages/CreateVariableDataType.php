<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariableDataTypes\Pages;

use App\Filament\Resources\LookupPages\CreateLookupRecord;
use App\Filament\Resources\VariableDataTypes\VariableDataTypeResource;

class CreateVariableDataType extends CreateLookupRecord
{
    protected static string $resource = VariableDataTypeResource::class;
}
