<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOosEvents\Pages;

use App\Filament\Resources\LaboratoryOosEvents\LaboratoryOosEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListLaboratoryOosEvents extends ListRecords
{
    protected static string $resource = LaboratoryOosEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
