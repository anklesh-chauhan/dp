<?php

namespace App\Filament\Resources\PdfAccessPolicies\Pages;

use App\Domain\Shared\Services\AuditLogService;
use App\Filament\Resources\PdfAccessPolicies\PdfAccessPolicyResource;
use App\Models\SopAuditLog;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPdfAccessPolicy extends EditRecord
{
    protected static string $resource = PdfAccessPolicyResource::class;

    /** @var array<string, mixed> */
    private array $originalPolicy = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (): void {
                    app(AuditLogService::class)->log(
                        action: SopAuditLog::ACTION_PDF_POLICY_DELETED,
                        oldValues: $this->record->load('roles')->toArray(),
                    );
                }),
        ];
    }

    protected function beforeSave(): void
    {
        $this->originalPolicy = $this->record->load('roles')->toArray();
    }

    protected function afterSave(): void
    {
        app(AuditLogService::class)->log(
            action: SopAuditLog::ACTION_PDF_POLICY_UPDATED,
            oldValues: $this->originalPolicy,
            newValues: $this->record->load('roles')->toArray(),
        );
    }
}
