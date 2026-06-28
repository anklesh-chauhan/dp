<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopWorkflows\Pages;

use App\Filament\Resources\SopWorkflows\SopWorkflowResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSopWorkflow extends ViewRecord
{
    protected static string $resource = SopWorkflowResource::class;

    protected function getActions(): array
    {
        return [EditAction::make()];
    }
}
