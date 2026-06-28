<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopWorkflows\Pages;

use App\Filament\Resources\SopWorkflows\SopWorkflowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSopWorkflow extends CreateRecord
{
    protected static string $resource = SopWorkflowResource::class;
}
