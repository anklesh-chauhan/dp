<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScheduleMGapAssessments\Pages;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\ScheduleMGapAssessments\ScheduleMGapAssessmentResource;
use Database\Seeders\ScheduleMGapAssessmentSeeder;
use Filament\Resources\Pages\CreateRecord;

final class CreateScheduleMGapAssessment extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = ScheduleMGapAssessmentResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.schedule-m-gap-assessments.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['status'] = ScheduleMGapAssessmentStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var ScheduleMGapAssessment $record */
        $record = $this->record;
        app(ScheduleMGapAssessmentSeeder::class)->seedPartIChecklist($record);
        $this->clearFormDraft();
    }
}
