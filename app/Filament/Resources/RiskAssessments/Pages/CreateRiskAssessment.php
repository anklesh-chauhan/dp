<?php

declare(strict_types=1);

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Services\RiskAssessmentLinkService;
use App\Filament\Concerns\AutosavesFormDraft;
use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

final class CreateRiskAssessment extends CreateRecord
{
    use AutosavesFormDraft;

    protected static string $resource = RiskAssessmentResource::class;

    /** @var array<string, mixed> */
    private array $pendingLinkSelections = [];

    public function mount(): void
    {
        parent::mount();
        $this->mountAutosavesFormDraft();
    }

    protected function draftFormKey(): string
    {
        return 'qms.risk-assessments.create';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingLinkSelections = $this->extractLinkSelections();

        $data['created_by'] = auth()->id();
        $data['status'] = RiskAssessmentStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncPendingLinks();
        $this->clearFormDraft();
    }

    /** @return array<string, int|null> */
    private function extractLinkSelections(): array
    {
        $state = $this->form->getRawState();

        return [
            'change_control_id' => $state['change_control_id'] ?? null,
            'deviation_id' => $state['deviation_id'] ?? null,
            'capa_id' => $state['capa_id'] ?? null,
            'complaint_id' => $state['complaint_id'] ?? null,
            'audit_finding_id' => $state['audit_finding_id'] ?? null,
            'csv_validation_project_id' => $state['csv_validation_project_id'] ?? null,
            'investigation_id' => $state['investigation_id'] ?? null,
        ];
    }

    private function syncPendingLinks(): void
    {
        if ($this->pendingLinkSelections === []) {
            return;
        }

        /** @var User $actor */
        $actor = auth()->user();
        /** @var RiskAssessment $record */
        $record = $this->record;

        app(RiskAssessmentLinkService::class)->syncSelections(
            $record,
            $this->pendingLinkSelections,
            $actor,
        );
    }
}
