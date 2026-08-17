<?php

declare(strict_types=1);

namespace App\Filament\Resources\InternalAudits\Pages;

use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\InternalAudits\InternalAuditResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateInternalAudit extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = InternalAuditResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.internal-audits.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = InternalAuditStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
