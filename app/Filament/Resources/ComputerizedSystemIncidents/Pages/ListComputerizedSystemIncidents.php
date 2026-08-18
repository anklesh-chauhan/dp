<?php

declare(strict_types=1);

namespace App\Filament\Resources\ComputerizedSystemIncidents\Pages;

use App\Filament\Resources\ComputerizedSystemIncidents\ComputerizedSystemIncidentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListComputerizedSystemIncidents extends ListRecords
{
    protected static string $resource = ComputerizedSystemIncidentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
