<?php

declare(strict_types=1);

namespace App\Filament\Resources\ApprovalDecisions\Pages;

use App\Filament\Resources\ApprovalDecisions\ApprovalDecisionResource;
use App\Filament\Resources\LookupPages\EditLookupRecord;

class EditApprovalDecision extends EditLookupRecord
{
    protected static string $resource = ApprovalDecisionResource::class;
}
