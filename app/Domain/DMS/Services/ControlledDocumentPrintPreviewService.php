<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Models\ControlledDocument;
use App\Models\Organization;
use App\Models\ReportTemplate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ControlledDocumentPrintPreviewService
{
    /**
     * @return array{
     *     document: ControlledDocument,
     *     issuance: null,
     *     reportTemplate: ReportTemplate,
     *     enabledFields: list<string>,
     *     organization: array<string, mixed>,
     *     serverPdf: false
     * }
     */
    public function viewData(ControlledDocument $document, ?int $reportTemplateId = null): array
    {
        $document->loadMissing([
            'approvals.approver.designation',
            'approvals.approvalDecision',
            'approvals.workflowStep.department',
            'approvals.workflowStep.approvalStepType',
            'attachments',
            'creator.department',
            'creator.designation',
            'department',
            'documentStatus',
            'documentType',
            'organization',
            'owner.department',
            'owner.designation',
            'sections',
            'template',
            'variables',
            'versionHistory.creator',
            'versionHistory.documentStatus',
        ]);

        $reportTemplate = $this->resolveReportTemplate($document, $reportTemplateId);
        $organization = $this->organizationIdentity($document);

        return [
            'document' => $document,
            'issuance' => null,
            'reportTemplate' => $reportTemplate,
            'enabledFields' => $reportTemplate->enabledFieldKeys(),
            'organization' => $organization,
            'serverPdf' => false,
        ];
    }

    private function resolveReportTemplate(ControlledDocument $document, ?int $reportTemplateId): ReportTemplate
    {
        $selectedId = $reportTemplateId ?? $document->template?->report_template_id;

        if ($selectedId === null) {
            throw new HttpException(
                422,
                'Select and save a Print & Report Template on the source Document Template before opening the preview.',
            );
        }

        $reportTemplate = ReportTemplate::query()
            ->active()
            ->where('scope', ReportScope::ControlledDocument)
            ->where('format', ReportFormat::Pdf)
            ->whereKey($selectedId)
            ->first();

        if ($reportTemplate === null) {
            throw new NotFoundHttpException('The selected Print & Report Template is unavailable.');
        }

        return $reportTemplate;
    }

    /** @return array<string, mixed> */
    private function organizationIdentity(ControlledDocument $document): array
    {
        if (filled($document->organization_snapshot)) {
            return $document->organization_snapshot;
        }

        return ($document->organization ?? Organization::defaultActive())?->identitySnapshot() ?? [];
    }
}
