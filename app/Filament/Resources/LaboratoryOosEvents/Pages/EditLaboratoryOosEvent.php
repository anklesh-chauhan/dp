<?php

declare(strict_types=1);

namespace App\Filament\Resources\LaboratoryOosEvents\Pages;

use App\Filament\Resources\LaboratoryOosEvents\LaboratoryOosEventResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditLaboratoryOosEvent extends EditRecord
{
    protected static string $resource = LaboratoryOosEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
