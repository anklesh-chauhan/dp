<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductRecalls\Pages;

use App\Domain\QMS\Enums\ProductRecallClassification;
use App\Domain\QMS\Enums\ProductRecallStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\ProductRecalls\ProductRecallResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProductRecall extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = ProductRecallResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.product_recalls.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = ProductRecallStatus::Draft->value;
        $data['classification'] = ProductRecallClassification::NotClassified->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
