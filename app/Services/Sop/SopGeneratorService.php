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
        private readonly AuditLogService $auditLogService,
        private readonly SopReferenceService $sopReferenceService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function generate(SopDocumentData $data): SopDocument
    {
        return DB::transaction(function () use ($data): SopDocument {
            if ($data->templateVersionId !== null) {
                $version = SopTemplateVersion::query()
                    ->with(['template.department', 'template.documentType', 'sections', 'variables'])
                    ->findOrFail($data->templateVersionId);

                if ($version->template->id !== $data->templateId) {
                    throw ValidationException::withMessages(['template_version_id' => 'The selected template version does not belong to the chosen template.']);
                }

                if ($version->status !== TemplateStatus::Published) {
                    throw ValidationException::withMessages(['template_version_id' => 'Only published template versions can generate controlled documents.']);
                }

                $template = $version->template;
            } else {
                $template = SopTemplate::query()
                    ->with(['department', 'documentType', 'publishedVersion.sections', 'publishedVersion.variables'])
                    ->findOrFail($data->templateId);

                if ($template->status !== TemplateStatus::Published || $template->publishedVersion === null) {
                    throw ValidationException::withMessages(['template_id' => 'Only published templates can generate controlled documents.']);
                }

                $version = $template->publishedVersion;
            }

            $documentType = $template->documentType;
            $sopReference = [];

            if ($documentType->requiresSopReference()) {
                if ($data->referencedSopDocumentId === null) {
                    throw ValidationException::withMessages([
                        'referenced_sop_document_id' => 'A referenced effective SOP is required for this document type.',
                    ]);
                }

                $sopReference = $this->sopReferenceService->resolve($data->referencedSopDocumentId, $template->department);
            }

            $typeCode = $documentType->code;
            $documentNumber = $data->documentNumber ?? $this->documentNumberGeneratorService->generate($template->department, $typeCode);
            $variables = $this->prepareVariables($data->variables, $template, $documentNumber, $data, $sopReference);
            $resolvedVariables = $this->resolveVariables($version, $variables);

            $document = SopDocument::query()->create([
                'template_id' => $template->id,
                'template_version_id' => $version->id,
                'document_number' => $documentNumber,
                'title' => $data->title,
                'version' => 1,
                'department_id' => $template->department_id,
                'document_type_id' => $documentType->id,
                'status' => DocumentStatus::Draft,
                'effective_date' => $data->effectiveDate,
                'review_date' => $data->reviewDate,
                'owner_id' => $data->ownerId,
                'created_by' => $data->createdBy,
                'batch_number' => $data->batchNumber,
                'product_name' => $data->productName,
                'purpose' => $data->purpose,
                ...$sopReference,
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

            $this->auditLogService->log(
                action: SopAuditLog::ACTION_GENERATED_SOP,
                newValues: [
                    'template_id' => $template->id,
                    'template_version_id' => $version->id,
                    'document_type_id' => $documentType->id,
                    'referenced_sop_document_id' => $sopReference['referenced_sop_document_id'] ?? null,
                ],
                userId: $data->createdBy,
                document: $document,
            );

            return $document->refresh()->load(['sections', 'variables', 'documentType', 'referencedSop']);
        });
    }

    /**
     * @param  array<string, mixed>  $variables
     * @param  array<string, mixed>  $sopReference
     * @return array<string, mixed>
     */
    private function prepareVariables(
        array $variables,
        SopTemplate $template,
        string $documentNumber,
        SopDocumentData $data,
        array $sopReference,
    ): array {
        if (isset($variables['referenced_sop']) && is_numeric($variables['referenced_sop'])) {
            $referencedSop = SopDocument::query()->find((int) $variables['referenced_sop']);

            if ($referencedSop !== null) {
                $variables['referenced_sop'] = $referencedSop->document_number;
            }
        }

        $variables = array_merge($variables, [
            'document_number' => $documentNumber,
            'effective_date' => $data->effectiveDate?->toDateString(),
            'review_date' => $data->reviewDate?->toDateString(),
        ]);

        if (blank($variables['department'] ?? null)) {
            $variables['department'] = $template->department->name;
        }

        if (! empty($sopReference['referenced_sop_number'])) {
            $variables['referenced_sop'] = $sopReference['referenced_sop_number'];
        }

        if (filled($data->batchNumber)) {
            $variables['batch_number'] = $data->batchNumber;
        }

        if (filled($data->productName)) {
            $variables['product_name'] = $data->productName;
        }

        return $variables;
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, string>
     *
     * @throws ValidationException
     */
    private function resolveVariables(SopTemplateVersion $version, array $variables): array
    {
        try {
            return $this->variableResolverService->resolveValues($version, $variables);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(
                collect($exception->errors())
                    ->mapWithKeys(fn (array $messages, string $key): array => ["variables.{$key}" => $messages])
                    ->all()
            );
        }
    }
}
