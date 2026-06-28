<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplates\Pages;

use App\Filament\Resources\SopTemplates\SopTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSopTemplate extends ViewRecord
{
    protected static string $resource = SopTemplateResource::class;

    protected function getActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
