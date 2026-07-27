<?php

declare(strict_types=1);

namespace App\Filament\Resources\SopTemplateApprovalInstances\Pages;

use App\Filament\Resources\SopTemplateApprovalInstances\SopTemplateApprovalInstanceResource;
use Filament\Resources\Pages\ListRecords;

class ListSopTemplateApprovalInstances extends ListRecords
{
    protected static string $resource = SopTemplateApprovalInstanceResource::class;
}
