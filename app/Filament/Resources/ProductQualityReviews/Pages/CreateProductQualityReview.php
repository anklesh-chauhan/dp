<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProductQualityReviews\Pages;

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\ProductQualityReviews\ProductQualityReviewResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateProductQualityReview extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = ProductQualityReviewResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.product-quality-reviews.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = ProductQualityReviewStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
