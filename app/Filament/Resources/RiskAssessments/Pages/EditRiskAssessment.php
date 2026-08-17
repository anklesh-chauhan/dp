<?php

declare(strict_types=1);

namespace App\Filament\Resources\RiskAssessments\Pages;

use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Services\RiskAssessmentLinkService;
use App\Filament\Resources\RiskAssessments\RiskAssessmentResource;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditRiskAssessment extends EditRecord
{
    protected static string $resource = RiskAssessmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var RiskAssessment $record */
        $record = $this->record;

        $data['change_control_id'] = $record->changeControls()->value('change_controls.id');
        $data['deviation_id'] = $record->deviations()->value('deviations.id');
        $data['capa_id'] = $record->capas()->value('capas.id');
        $data['complaint_id'] = $record->complaints()->value('complaints.id');
        $data['audit_finding_id'] = $record->auditFindings()->value('audit_findings.id');
        $data['csv_validation_project_id'] = $record->csvValidationProjects()->value('csv_validation_projects.id');
        $data['investigation_id'] = $record->investigations()->value('investigations.id');

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var User $actor */
        $actor = auth()->user();
        /** @var RiskAssessment $record */
        $record = $this->record;
        $state = $this->form->getRawState();

        app(RiskAssessmentLinkService::class)->syncSelections(
            $record,
            [
                'change_control_id' => $state['change_control_id'] ?? null,
                'deviation_id' => $state['deviation_id'] ?? null,
                'capa_id' => $state['capa_id'] ?? null,
                'complaint_id' => $state['complaint_id'] ?? null,
                'audit_finding_id' => $state['audit_finding_id'] ?? null,
                'csv_validation_project_id' => $state['csv_validation_project_id'] ?? null,
                'investigation_id' => $state['investigation_id'] ?? null,
            ],
            $actor,
        );
    }
}
