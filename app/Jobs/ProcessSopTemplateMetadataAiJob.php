<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiTask;
use App\Services\AI\Contracts\DocumentClassifier;
use App\Services\AI\Contracts\DocumentDescriptionGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

final class ProcessSopTemplateMetadataAiJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @var array<int, int>
     */
    public array $backoff = [
        30,
        120,
        300,
    ];

    public function __construct(
        public readonly int $aiTaskId,
    ) {}

    public function handle(
        DocumentDescriptionGenerator $descriptionGenerator,
        DocumentClassifier $classifier,
    ): void {
        $task = AiTask::query()->findOrFail($this->aiTaskId);

        try {
            $task->markAsProcessing(
                currentStep: 'Generating document description',
                progress: 10,
            );

            $input = $task->input;

            $name = $this->requiredString(
                input: $input,
                key: 'name',
            );

            $departmentName = $this->requiredString(
                input: $input,
                key: 'department_name',
            );

            $descriptionResult = $descriptionGenerator->generate(
                name: $name,
                departmentName: $departmentName,
            );

            $description = $descriptionResult['description'] ?? null;

            if (! is_string($description) || trim($description) === '') {
                throw new RuntimeException(
                    'AI description generation returned no usable description.',
                );
            }

            $description = trim($description);

            $task->updateProgress(
                progress: 50,
                currentStep: 'Classifying document category and type',
            );

            $classification = $classifier->classify(
                name: $name,
                description: $description,
                departmentName: $departmentName,
            );

            $categoryId = $classification['category_id'] ?? null;
            $documentTypeId = $classification['document_type_id'] ?? null;
            $regulationTagIds = $classification['regulation_tag_ids'] ?? null;

            if (
                ! is_int($categoryId)
                || ! is_int($documentTypeId)
                || ! is_array($regulationTagIds)
            ) {
                throw new RuntimeException(
                    'AI document classification returned an incomplete result.',
                );
            }

            $task->updateProgress(
                progress: 90,
                currentStep: 'Finalizing AI metadata result',
            );

            $task->markAsCompleted([
                'description' => $description,
                'reasoning' => $this->normalizeOptionalString(
                    $descriptionResult['reasoning'] ?? null,
                ),
                'category_id' => $categoryId,
                'document_type_id' => $documentTypeId,
                'regulation_tag_ids' => array_values(
                    array_map('intval', $regulationTagIds),
                ),
            ]);
        } catch (Throwable $exception) {
            $this->markTaskAsFailed(
                task: $task,
                exception: $exception,
            );

            throw $exception;
        }
    }

    private function requiredString(
        array $input,
        string $key,
    ): string {
        $value = $input[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(
                sprintf(
                    'AI task input [%s] is required.',
                    $key,
                ),
            );
        }

        return trim($value);
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }

    private function markTaskAsFailed(
        AiTask $task,
        Throwable $exception,
    ): void {
        if ($task->isFinished()) {
            return;
        }

        $task->markAsFailed(
            errorMessage: $exception->getMessage(),
        );
    }
}
