<?php

declare(strict_types=1);

namespace App\Filament\Resources\DocumentTemplateApprovalInstances\Pages;

use App\Filament\Resources\DocumentTemplateApprovalInstances\DocumentTemplateApprovalInstanceResource;
use Filament\Resources\Pages\ListRecords;

class ListDocumentTemplateApprovalInstances extends ListRecords
{
    protected static string $resource = DocumentTemplateApprovalInstanceResource::class;
}
