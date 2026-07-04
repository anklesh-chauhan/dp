<?php

declare(strict_types=1);

namespace App\Filament\Resources\VariableDataTypes\Pages;

use App\Filament\Resources\LookupPages\ListLookupRecords;
use App\Filament\Resources\VariableDataTypes\VariableDataTypeResource;

class ListVariableDataTypes extends ListLookupRecords
{
    protected static string $resource = VariableDataTypeResource::class;
}
