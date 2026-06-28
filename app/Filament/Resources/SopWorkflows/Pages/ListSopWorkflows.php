<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopWorkflows\Pages;

use App\Filament\Resources\SopWorkflows\SopWorkflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSopWorkflows extends ListRecords
{
    protected static string $resource = SopWorkflowResource::class;

    protected function getActions(): array
    {
        return [CreateAction::make()];
    }
}
