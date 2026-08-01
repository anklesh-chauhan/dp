<?php

namespace App\Filament\Resources\PdfAccessPolicies\Pages;

use App\Domain\Shared\Services\AuditLogService;
use App\Filament\Resources\PdfAccessPolicies\PdfAccessPolicyResource;
use App\Models\SopAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreatePdfAccessPolicy extends CreateRecord
{
    protected static string $resource = PdfAccessPolicyResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        app(AuditLogService::class)->log(
            action: SopAuditLog::ACTION_PDF_POLICY_CREATED,
            newValues: $this->record->load('roles')->toArray(),
        );
    }
}
