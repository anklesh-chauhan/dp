<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopWorkflows\Pages;

use App\Filament\Resources\SopWorkflows\SopWorkflowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSopWorkflow extends EditRecord
{
    protected static string $resource = SopWorkflowResource::class;

    protected function getActions(): array
    {
        return [DeleteAction::make()];
    }
}
