<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariableDataTypes\Pages;

use App\Filament\Resources\LookupPages\EditLookupRecord;
use App\Filament\Resources\VariableDataTypes\VariableDataTypeResource;

class EditVariableDataType extends EditLookupRecord
{
    protected static string $resource = VariableDataTypeResource::class;
}
