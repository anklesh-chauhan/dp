<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditFindings\Pages;

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\AuditFindings\AuditFindingResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateAuditFinding extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = AuditFindingResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.audit-findings.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['identified_by'] = auth()->id();
        $data['disposition'] = AuditFindingDisposition::Open->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
