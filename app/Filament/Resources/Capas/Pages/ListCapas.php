<?php

declare(strict_types=1);

namespace App\Filament\Resources\Capas\Pages;

use App\Filament\Resources\Capas\CapaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCapas extends ListRecords
{
    protected static string $resource = CapaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
