<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\TemplateStatus;
use App\Models\VariableDataType;
use App\Services\AI\Contracts\TemplateGenerator;
use App\Services\AI\Validation\GeneratedTemplateValidator;
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
        public SopTemplate $template,
        public string $regulationTags,
    ) {}

    public function handle(
        TemplateGenerator $aiService,
        GeneratedTemplateValidator $generatedTemplateValidator,
    ): void {
        try {
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

            $this->validateResult($result);

            $result = $this->validateOrRepairResult(
                result: $result,
                aiService: $aiService,
                generatedTemplateValidator: $generatedTemplateValidator,
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
                    'current_version' => 0,
                ]);

                $this->updateProgress(
                    status: 'Finalizing template generation...',
                    progress: 95,
                );

                $this->updateProgress(
                    status: SopTemplate::GENERATION_STATUS_COMPLETED,
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
     * @param array<string, mixed> $result
     *
     * @return array<string, mixed>
     */
    private function validateOrRepairResult(
        array $result,
        TemplateGenerator $aiService,
        GeneratedTemplateValidator $generatedTemplateValidator,
    ): array {
        try {
            $generatedTemplateValidator->validate($result);

            return $result;
        } catch (InvalidArgumentException $exception) {
            $repairedResult = $aiService->repairRegulatedTemplate(
                formData: $this->template->toArray(),
                regulationTags: $this->regulationTags,
                generatedTemplate: $result,
                validationError: $exception->getMessage(),
            );

            if ($repairedResult === null) {
                throw new RuntimeException(
                    'AI template repair failed to return a structured result.',
                    previous: $exception,
                );
            }

            $this->validateResult($repairedResult);

            $generatedTemplateValidator->validate($repairedResult);

            return $repairedResult;
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function validateResult(array $result): void
    {
        if (
            ! array_key_exists('sections', $result)
            || ! is_array($result['sections'])
        ) {
            throw new RuntimeException(
                'AI template generation returned invalid sections.',
            );
        }

        if (
            ! array_key_exists('variables', $result)
            || ! is_array($result['variables'])
        ) {
            throw new RuntimeException(
                'AI template generation returned invalid variables.',
            );
        }

        foreach ($result['sections'] as $section) {
            if (! is_array($section)) {
                throw new RuntimeException(
                    'AI template generation returned an invalid section.',
                );
            }

            $this->validateSection($section);
        }

        foreach ($result['variables'] as $variable) {
            if (! is_array($variable)) {
                throw new RuntimeException(
                    'AI template generation returned an invalid variable.',
                );
            }

            $this->validateVariable($variable);
        }
    }

    /**
     * @param array<string, mixed> $section
     */
    private function validateSection(array $section): void
    {
        foreach (
            [
                'title',
                'content',
                'section_order',
                'section_type',
            ] as $requiredKey
        ) {
            if (! array_key_exists($requiredKey, $section)) {
                throw new RuntimeException(
                    "AI template section is missing [{$requiredKey}].",
                );
            }
        }

        if (
            ! is_string($section['title'])
            || trim($section['title']) === ''
            || ! is_string($section['content'])
            || ! is_int($section['section_order'])
            || ! is_string($section['section_type'])
            || trim($section['section_type']) === ''
        ) {
            throw new RuntimeException(
                'AI template generation returned invalid section data.',
            );
        }
    }

    /**
     * @param array<string, mixed> $variable
     */
    private function validateVariable(array $variable): void
    {
        foreach (
            [
                'name',
                'label',
                'datatype',
                'default_value',
                'required',
            ] as $requiredKey
        ) {
            if (! array_key_exists($requiredKey, $variable)) {
                throw new RuntimeException(
                    "AI template variable is missing [{$requiredKey}].",
                );
            }
        }

        if (
            ! is_string($variable['name'])
            || trim($variable['name']) === ''
            || ! is_string($variable['label'])
            || trim($variable['label']) === ''
            || ! is_string($variable['datatype'])
            || trim($variable['datatype']) === ''
            || ! is_string($variable['default_value'])
            || ! is_bool($variable['required'])
        ) {
            throw new RuntimeException(
                'AI template generation returned invalid variable data.',
            );
        }
    }

    private function createOrResetVersion(): SopTemplateVersion
    {
        $version = $this->template
            ->versions()
            ->withTrashed()
            ->where('version', 0)
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
     * @param array<int, array<string, mixed>> $sections
     */
    private function persistSections(
        SopTemplateVersion $version,
        array $sections,
    ): void {
        foreach ($sections as $section) {
            $version->sections()->create([
                'title' => trim($section['title']),
                'section_order' => $section['section_order'],
                'section_type' => trim($section['section_type']),
                'content' => $section['content'],
                'is_required' => true,
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $variables
     */
    private function persistVariables(
        SopTemplateVersion $version,
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
            'generation_status' => SopTemplate::GENERATION_STATUS_FAILED,
            'generation_progress' => 0,
        ]);
    }
}
