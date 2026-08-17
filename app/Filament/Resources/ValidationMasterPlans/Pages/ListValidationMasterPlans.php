<?php

declare(strict_types=1);

namespace App\Filament\Resources\ValidationMasterPlans\Pages;

use App\Filament\Resources\ValidationMasterPlans\ValidationMasterPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListValidationMasterPlans extends ListRecords
{
    protected static string $resource = ValidationMasterPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
