<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovalDecisions\Pages;

use App\Filament\Resources\ApprovalDecisions\ApprovalDecisionResource;
use App\Filament\Resources\LookupPages\CreateLookupRecord;

class CreateApprovalDecision extends CreateLookupRecord
{
    protected static string $resource = ApprovalDecisionResource::class;
}
