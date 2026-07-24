<?php

declare(strict_types=1);

namespace App\Services\Sop;

use App\Data\SopDocumentData;
use App\Models\DocumentStatus;
use App\Models\SopAuditLog;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                    ->with(['template.department', 'template.documentType', 'templateStatus', 'sections', 'variables'])
                    ->findOrFail($data->templateVersionId);

                if ($version->template->id !== $data->templateId) {
                    throw ValidationException::withMessages(['template_version_id' => 'The selected template version does not belong to the chosen template.']);
                }

                if (! $version->templateStatus?->hasCode(TemplateStatus::PUBLISHED)) {
                    throw ValidationException::withMessages(['template_version_id' => 'Only published template versions can generate controlled documents.']);
                }

                $template = $version->template;
            } else {
                $template = SopTemplate::query()
                    ->with(['department', 'documentType', 'templateStatus', 'publishedVersion.sections', 'publishedVersion.variables'])
                    ->findOrFail($data->templateId);

                if (! $template->templateStatus?->hasCode(TemplateStatus::PUBLISHED) || $template->publishedVersion === null) {
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
                'document_series_id' => (string) Str::uuid(),
                'template_id' => $template->id,
                'template_version_id' => $version->id,
                'document_number' => $documentNumber,
                'title' => $data->title,
                'version' => 1,
                'department_id' => $template->department_id,
                'document_type_id' => $documentType->id,
                'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
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
                    'content' => $this->variableResolverService->replace(
                        $section->content ?? '',
                        $resolvedVariables['substitution'],
                    ),
                ]);
            }

            foreach ($resolvedVariables['storage'] as $name => $value) {
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
                    'regulation_tag_ids' => $data->regulationTagIds,
                    'referenced_sop_document_id' => $sopReference['referenced_sop_document_id'] ?? null,
                ],
                userId: $data->createdBy,
                document: $document,
            );

            if ($data->regulationTagIds !== []) {
                $document->regulationTags()->sync($data->regulationTagIds);
            }

            return $document->refresh()->load(['sections', 'variables', 'documentType', 'referencedSop', 'regulationTags']);
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
        $variables = array_merge($variables, [
            'document_number' => $documentNumber,
            'effective_date' => $data->effectiveDate?->toDateString(),
            'review_date' => $data->reviewDate?->toDateString(),
        ]);

        if (blank($variables['department'] ?? null)) {
            $variables['department'] = $template->department_id;
        }

        if (! empty($sopReference['referenced_sop_document_id'])) {
            $variables['referenced_sop'] = $sopReference['referenced_sop_document_id'];
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
     * @return array{storage: array<string, string>, substitution: array<string, string>}
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
