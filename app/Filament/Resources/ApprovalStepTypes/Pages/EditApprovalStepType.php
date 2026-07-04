<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovalStepTypes\Pages;

use App\Filament\Resources\ApprovalStepTypes\ApprovalStepTypeResource;
use App\Filament\Resources\LookupPages\EditLookupRecord;

class EditApprovalStepType extends EditLookupRecord
{
    protected static string $resource = ApprovalStepTypeResource::class;
}
