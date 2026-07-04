<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovalStepTypes\Pages;

use App\Filament\Resources\ApprovalStepTypes\ApprovalStepTypeResource;
use App\Filament\Resources\LookupPages\CreateLookupRecord;

class CreateApprovalStepType extends CreateLookupRecord
{
    protected static string $resource = ApprovalStepTypeResource::class;
}
