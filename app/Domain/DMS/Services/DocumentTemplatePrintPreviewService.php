<?php

declare(strict_types=1);

namespace App\Domain\DMS\Services;

use App\Domain\Reporting\Enums\ReportFormat;
use App\Domain\Reporting\Enums\ReportScope;
use App\Models\ControlledDocument;
use App\Models\ControlledDocumentVariable;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVersion;
use App\Models\Organization;
use App\Models\ReportTemplate;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DocumentTemplatePrintPreviewService
{
    public function __construct(
        private readonly VariableResolverService $variableResolver,
    ) {}

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
    public function viewData(
        DocumentTemplate $documentTemplate,
        ?DocumentTemplateVersion $documentTemplateVersion = null,
    ): array {
        $version = $this->resolveVersion($documentTemplate, $documentTemplateVersion);
        $reportTemplate = $this->resolveReportTemplate($documentTemplate);
        $organization = Organization::defaultActive()?->identitySnapshot() ?? [];

        return [
            'document' => $this->buildPreviewDocument($documentTemplate, $version),
            'issuance' => null,
            'reportTemplate' => $reportTemplate,
            'enabledFields' => $reportTemplate->enabledFieldKeys(),
            'organization' => $organization,
            'serverPdf' => false,
        ];
    }

    private function resolveVersion(
        DocumentTemplate $documentTemplate,
        ?DocumentTemplateVersion $documentTemplateVersion,
    ): DocumentTemplateVersion {
        if ($documentTemplateVersion instanceof DocumentTemplateVersion) {
            if ($documentTemplateVersion->document_template_id !== $documentTemplate->getKey()) {
                throw new NotFoundHttpException;
            }

            return $documentTemplateVersion->loadMissing([
                'sections',
                'variables',
            ]);
        }

        $version = $documentTemplate->latestDraftVersion()
            ->with(['sections', 'variables'])
            ->first();

        if ($version === null) {
            throw new NotFoundHttpException('This template does not have a version to preview.');
        }

        return $version;
    }

    private function resolveReportTemplate(DocumentTemplate $documentTemplate): ReportTemplate
    {
        if ($documentTemplate->report_template_id === null) {
            throw new HttpException(
                422,
                'Select and save a Print & Report Template before opening the preview.',
            );
        }

        $reportTemplate = ReportTemplate::query()
            ->active()
            ->where('scope', ReportScope::ControlledDocument)
            ->where('format', ReportFormat::Pdf)
            ->whereKey($documentTemplate->report_template_id)
            ->first();

        if ($reportTemplate === null) {
            throw new NotFoundHttpException('The selected Print & Report Template is unavailable.');
        }

        return $reportTemplate;
    }

    private function buildPreviewDocument(
        DocumentTemplate $documentTemplate,
        DocumentTemplateVersion $version,
    ): ControlledDocument {
        $documentTemplate->loadMissing(['department', 'templateStatus']);

        $document = new ControlledDocument([
            'document_number' => $documentTemplate->code,
            'title' => $documentTemplate->name,
            'version' => $version->version,
            'purpose' => $documentTemplate->description,
            'department_id' => $documentTemplate->department_id,
            'effective_date' => $version->effective_date,
        ]);

        $status = new DocumentStatus([
            'name' => $version->approval_status->label(),
            'code' => DocumentStatus::DRAFT,
        ]);

        $substitution = $version->variables
            ->mapWithKeys(fn ($variable): array => [
                $variable->name => filled($variable->default_value)
                    ? (string) $variable->default_value
                    : '[Sample value]',
            ])
            ->all();

        /** @var Collection<int, DocumentTemplateSection> $sections */
        $sections = $version->sections
            ->map(function (DocumentTemplateSection $section) use ($substitution): DocumentTemplateSection {
                $previewSection = $section->replicate();
                $previewSection->id = $section->getKey();
                $previewSection->exists = true;
                $previewSection->content = $this->variableResolver->replace(
                    (string) ($section->content ?? ''),
                    $substitution,
                );

                return $previewSection;
            })
            ->values();

        /** @var Collection<int, ControlledDocumentVariable> $variables */
        $variables = $version->variables
            ->map(fn ($variable): ControlledDocumentVariable => new ControlledDocumentVariable([
                'variable_name' => $variable->name,
                'value' => filled($variable->default_value)
                    ? (string) $variable->default_value
                    : '[Sample value]',
            ]))
            ->values();

        $document->setRelation('department', $documentTemplate->department);
        $document->setRelation('documentStatus', $status);
        $document->setRelation('sections', $sections);
        $document->setRelation('variables', $variables);
        $document->setRelation('approvals', collect());
        $document->setRelation('attachments', collect());
        $document->setRelation('owner', null);
        $document->setRelation('creator', null);

        return $document;
    }
}
