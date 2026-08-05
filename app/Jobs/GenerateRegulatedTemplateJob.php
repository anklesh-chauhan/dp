<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\DocumentTemplate\AI\Services\DocumentTemplateIntegrityService;
use App\Enums\ProductModule;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\VariableDataType;
use App\Services\AI\Contracts\TemplateGenerator;
use App\Support\Modules\ModuleManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class GenerateRegulatedTemplateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public DocumentTemplate $template,
        public string $regulationTags,
    ) {}

    public function handle(
        TemplateGenerator $aiService,
        DocumentTemplateIntegrityService $integrityService,
    ): void {
        app(ModuleManager::class)->ensureEnabled(ProductModule::AI);

        try {
            $this->template->loadMissing([
                'category:id,name,code',
                'documentType:id,name,code',
            ]);

            $this->updateProgress(
                status: 'Connecting to AI service...',
                progress: 15,
            );

            $result = $aiService->generateRegulatedTemplate(
                formData: $this->template->toArray(),
                regulationTags: $this->regulationTags,
            );

            if ($result === null) {
                $this->markAsFailed();

                return;
            }

            $result = $this->validateOrRepairResult(
                result: $result,
                aiService: $aiService,
                integrityService: $integrityService,
            );

            $this->updateProgress(
                status: 'Structuring compliance sections and layout elements...',
                progress: 50,
            );

            DB::transaction(function () use ($result): void {
                $version = $this->createOrResetVersion();

                $this->persistSections(
                    version: $version,
                    sections: $result['sections'],
                );

                $this->updateProgress(
                    status: 'Generating variable definitions...',
                    progress: 75,
                );

                $this->persistVariables(
                    version: $version,
                    variables: $result['variables'],
                );

                $this->template->update([
                    'current_version' => $version->version,
                ]);

                $this->updateProgress(
                    status: 'Finalizing template generation...',
                    progress: 95,
                );

                $this->updateProgress(
                    status: DocumentTemplate::GENERATION_STATUS_COMPLETED,
                    progress: 100,
                );
            });
        } catch (Throwable $exception) {
            $this->markAsFailed();

            Log::error(
                'Regulated template generation job failed.',
                [
                    'exception' => $exception,
                    'template_id' => $this->template->getKey(),
                    'template_name' => $this->template->name,
                ],
            );

            throw $exception;
        }
    }

    /**
     * Validate generated template integrity or perform exactly one repair attempt.
     *
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    private function validateOrRepairResult(
        array $result,
        TemplateGenerator $aiService,
        DocumentTemplateIntegrityService $integrityService,
    ): array {
        $validation = $integrityService->validate($result);

        if ($validation->passed()) {
            return $result;
        }

        $validationError = $integrityService->failureMessage($validation);

        if ($integrityService->hasStructuralErrors($validation)) {
            throw new RuntimeException(
                $validationError,
            );
        }

        $repairedResult = $aiService->repairRegulatedTemplate(
            formData: $this->template->toArray(),
            regulationTags: $this->regulationTags,
            generatedTemplate: $result,
            validationError: $validationError,
        );

        if ($repairedResult === null) {
            throw new RuntimeException(
                'AI template repair failed to return a structured result.',
            );
        }

        $repairedValidation = $integrityService->validate($repairedResult);

        if ($repairedValidation->failed()) {
            throw new InvalidArgumentException(
                $integrityService->failureMessage($repairedValidation),
            );
        }

        return $repairedResult;
    }

    private function createOrResetVersion(): DocumentTemplateVersion
    {
        $version = $this->template
            ->versions()
            ->withTrashed()
            ->where('version', 1)
            ->first();

        if ($version === null) {
            return $this->template->versions()->create([
                'version' => 1,
                'template_status_id' => TemplateStatus::idFor(
                    TemplateStatus::DRAFT,
                ),
                'change_reason' => 'Auto-generated base boilerplate compliant with specified regulation tags.',
                'created_by' => $this->template->created_by,
            ]);
        }

        if ($version->trashed()) {
            $version->restore();
        }

        $version->update([
            'template_status_id' => TemplateStatus::idFor(
                TemplateStatus::DRAFT,
            ),
            'change_reason' => 'Auto-generated base boilerplate compliant with specified regulation tags.',
            'created_by' => $this->template->created_by,
        ]);

        $version->sections()->delete();
        $version->variables()->delete();

        return $version;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    private function persistSections(
        DocumentTemplateVersion $version,
        array $sections,
    ): void {
        foreach ($sections as $section) {
            $version->sections()->create([
                'title' => trim($section['title']),
                'section_order' => $section['section_order'],
                'heading_level' => max(1, min(6, (int) ($section['heading_level'] ?? 1))),
                'section_type' => 'rich_text',
                'include_in_toc' => (bool) ($section['include_in_toc'] ?? true),
                'toc_title' => trim((string) ($section['toc_title'] ?? '')) ?: null,
                'content' => $section['content'],
                'is_required' => true,
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variables
     */
    private function persistVariables(
        DocumentTemplateVersion $version,
        array $variables,
    ): void {
        $dataTypeMap = VariableDataType::query()
            ->pluck('id', 'code')
            ->mapWithKeys(
                fn (mixed $id, mixed $code): array => [
                    strtolower((string) $code) => $id,
                ],
            )
            ->all();

        $fallbackDataTypeId = $dataTypeMap[
            VariableDataType::TEXT
        ] ?? null;

        foreach ($variables as $variable) {
            $dataTypeCode = strtolower(
                trim($variable['datatype']),
            );

            $dataTypeId = $dataTypeMap[$dataTypeCode]
                ?? $fallbackDataTypeId;

            if ($dataTypeId === null) {
                throw new RuntimeException(
                    "Unable to resolve variable data type [{$dataTypeCode}] and no text fallback exists.",
                );
            }

            $version->variables()->create([
                'name' => trim($variable['name']),
                'label' => trim($variable['label']),
                'variable_data_type_id' => $dataTypeId,
                'default_value' => $variable['default_value'],
                'required' => $variable['required'],
            ]);
        }
    }

    private function updateProgress(
        string $status,
        int $progress,
    ): void {
        $this->template->update([
            'generation_status' => $status,
            'generation_progress' => $progress,
        ]);
    }

    private function markAsFailed(): void
    {
        $this->template->update([
            'generation_status' => DocumentTemplate::GENERATION_STATUS_FAILED,
            'generation_progress' => 0,
        ]);
    }
}
