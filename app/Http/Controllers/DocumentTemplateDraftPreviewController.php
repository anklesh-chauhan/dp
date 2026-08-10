<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Domain\Reporting\Support\PrintLayoutRegistry;
use App\Domain\Reporting\Support\ReportFieldRegistry;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\ReportTemplate;
use Illuminate\Contracts\View\View;

class DocumentTemplateDraftPreviewController extends Controller
{
    public function __invoke(
        DocumentTemplate $documentTemplate,
        ?DocumentTemplateVersion $documentTemplateVersion = null,
    ): View {
        if ($documentTemplateVersion instanceof DocumentTemplateVersion) {
            abort_unless(
                $documentTemplateVersion->document_template_id === $documentTemplate->getKey(),
                404,
            );

            $version = $documentTemplateVersion->load([
                'approvalInstances.decider',
                'approvalInstances.workflowStep.approvalStepType',
                'sections',
                'variables',
            ]);
        } else {
            $version = $documentTemplate->latestDraftVersion()
                ->with([
                    'approvalInstances.decider',
                    'approvalInstances.workflowStep.approvalStepType',
                    'sections',
                    'variables',
                ])
                ->first();
        }

        abort_unless($version !== null, 404, 'This template does not have a draft version to preview.');

        $reportTemplate = ReportTemplate::query()
            ->active()
            ->where('scope', ReportScope::ControlledDocument)
            ->where('format', ReportFormat::Pdf)
            ->when(
                $documentTemplate->report_template_id,
                fn ($query) => $query->whereKey($documentTemplate->report_template_id),
                fn ($query) => $query->where('is_system', true)->oldest(),
            )
            ->first();

        if ($reportTemplate === null) {
            $layoutRegistry = app(PrintLayoutRegistry::class);

            $reportTemplate = new ReportTemplate([
                'layout_key' => 'sop-gmp-standard',
                'name' => 'GMP SOP Header / Footer',
                'scope' => ReportScope::ControlledDocument,
                'format' => ReportFormat::Pdf,
                'fields' => app(ReportFieldRegistry::class)->defaultFields(ReportScope::ControlledDocument),
                'page_settings' => $layoutRegistry->defaultPageSettings(),
                'header_zones' => $layoutRegistry->defaultHeaderZones(),
                'footer_zones' => $layoutRegistry->defaultFooterZones(),
                'is_active' => true,
                'is_system' => true,
            ]);
        }

        return view('document-templates.draft-preview', [
            'template' => $documentTemplate->load(['creator', 'department', 'documentType']),
            'version' => $version,
            'reportTemplate' => $reportTemplate,
            'previewLabel' => $documentTemplateVersion instanceof DocumentTemplateVersion
                ? 'Version Preview'
                : 'Draft Preview',
            'pageSettings' => $reportTemplate->printPageSettings(),
            'headerZones' => $reportTemplate->printHeaderZones(),
            'footerZones' => $reportTemplate->printFooterZones(),
        ]);
    }
}
