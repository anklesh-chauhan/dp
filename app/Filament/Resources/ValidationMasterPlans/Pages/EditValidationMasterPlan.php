<?php

declare(strict_types=1);

namespace App\Filament\Resources\ValidationMasterPlans\Pages;

use App\Filament\Resources\ValidationMasterPlans\ValidationMasterPlanResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditValidationMasterPlan extends EditRecord
{
    protected static string $resource = ValidationMasterPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
