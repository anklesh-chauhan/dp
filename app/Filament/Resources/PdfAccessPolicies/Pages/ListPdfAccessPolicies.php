<?php

namespace App\Filament\Resources\PdfAccessPolicies\Pages;

use App\Filament\Resources\PdfAccessPolicies\PdfAccessPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPdfAccessPolicies extends ListRecords
{
    protected static string $resource = PdfAccessPolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
