<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Data\SopDocumentData;
use App\Enums\DocumentStatus;
use App\Enums\TemplateStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SopGeneratorService
{
    public function __construct(
        private readonly VariableResolverService $variableResolverService,
        private readonly DocumentNumberGeneratorService $documentNumberGeneratorService,
        private readonly WorkflowEngineService $workflowEngineService,
        private readonly AuditLogService $auditLogService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function generate(SopDocumentData $data): SopDocument
    {
        return DB::transaction(function () use ($data): SopDocument {
            if ($data->templateVersionId !== null) {
                $version = SopTemplateVersion::query()
                    ->with(['template.department', 'sections', 'variables'])
                    ->findOrFail($data->templateVersionId);

                if ($version->template->id !== $data->templateId) {
                    throw ValidationException::withMessages(['template_version_id' => 'The selected template version does not belong to the chosen template.']);
                }

                if ($version->status !== TemplateStatus::Published) {
                    throw ValidationException::withMessages(['template_version_id' => 'Only published template versions can generate SOP documents.']);
                }

                $template = $version->template;
            } else {
                $template = SopTemplate::query()
                    ->with(['department', 'publishedVersion.sections', 'publishedVersion.variables'])
                    ->findOrFail($data->templateId);

                if ($template->status !== TemplateStatus::Published || $template->publishedVersion === null) {
                    throw ValidationException::withMessages(['template_id' => 'Only published templates can generate SOP documents.']);
                }

                $version = $template->publishedVersion;
            }

            $documentNumber = $data->documentNumber ?? $this->documentNumberGeneratorService->generate($template->department);
            $variables = array_merge($data->variables, [
                'department' => $template->department->name,
                'document_number' => $documentNumber,
                'effective_date' => $data->effectiveDate?->toDateString(),
                'review_date' => $data->reviewDate?->toDateString(),
            ]);
            $resolvedVariables = $this->variableResolverService->resolveValues($version, $variables);

            $document = SopDocument::query()->create([
                'template_id' => $template->id,
                'template_version_id' => $version->id,
                'document_number' => $documentNumber,
                'title' => $data->title,
                'version' => 1,
                'department_id' => $template->department_id,
                'status' => DocumentStatus::Draft,
                'effective_date' => $data->effectiveDate,
                'review_date' => $data->reviewDate,
                'owner_id' => $data->ownerId,
                'created_by' => $data->createdBy,
            ]);

            foreach ($version->sections as $section) {
                $document->sections()->create([
                    'title' => $section->title,
                    'section_order' => $section->section_order,
                    'content' => $this->variableResolverService->replace($section->content ?? '', $resolvedVariables),
                ]);
            }

            foreach ($resolvedVariables as $name => $value) {
                $document->variables()->create([
                    'variable_name' => $name,
                    'value' => $value,
                ]);
            }

            $this->auditLogService->log($document, SopAuditLog::ACTION_GENERATED_SOP, null, [
                'template_id' => $template->id,
                'template_version_id' => $version->id,
            ], $data->createdBy);

            $this->workflowEngineService->start($document);

            return $document->refresh()->load(['sections', 'variables', 'approvals']);
        });
    }
}
