<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovalStepTypes\Pages;

use App\Filament\Resources\ApprovalStepTypes\ApprovalStepTypeResource;
use App\Filament\Resources\LookupPages\ListLookupRecords;

class ListApprovalStepTypes extends ListLookupRecords
{
    protected static string $resource = ApprovalStepTypeResource::class;
}
