<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Filament\Resources\SopTemplates\SopTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSopTemplates extends ListRecords
{
    protected static string $resource = SopTemplateResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
