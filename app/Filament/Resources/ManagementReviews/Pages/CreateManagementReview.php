<?php

declare(strict_types=1);

namespace App\Filament\Resources\ManagementReviews\Pages;

use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\ManagementReviews\ManagementReviewResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateManagementReview extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = ManagementReviewResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.management-reviews.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = ManagementReviewStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->clearFormDraft();
    }
}
