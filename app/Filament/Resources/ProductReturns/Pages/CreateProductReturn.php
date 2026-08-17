<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductReturns\Pages;

use App\Domain\QMS\Enums\ProductReturnDisposition;
use App\Domain\QMS\Enums\ProductReturnStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\ProductReturns\ProductReturnResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProductReturn extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = ProductReturnResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.product_returns.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = ProductReturnStatus::Draft->value;
        $data['disposition'] = ProductReturnDisposition::Pending->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
