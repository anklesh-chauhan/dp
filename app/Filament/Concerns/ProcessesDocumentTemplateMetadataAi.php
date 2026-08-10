<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Enums\ProductModule;
use App\Jobs\ProcessDocumentTemplateMetadataAiJob;
use App\Models\AiTask;
use App\Models\Department;
use App\Models\DocumentTemplate;
use App\Services\AI\Enums\AiTaskStatus;
use App\Services\AI\Enums\AIUseCase;
use App\Support\Modules\ModuleManager;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Throwable;

trait ProcessesDocumentTemplateMetadataAi
{
    public ?int $metadataAiTaskId = null;

    public bool $metadataAiTaskPolling = false;

    public int $metadataAiProgress = 0;

    public ?string $metadataAiCurrentStep = null;

    public function startMetadataAiGeneration(): void
    {
        app(ModuleManager::class)->ensureEnabled(ProductModule::AI);

        if ($this->metadataAiTaskPolling) {
            return;
        }

        if (
            property_exists($this, 'record')
            && $this->record instanceof DocumentTemplate
            && ! $this->record->isEditable()
        ) {
            Notification::make()
                ->danger()
                ->title('Template is locked')
                ->body('Published templates cannot be changed with AI.')
                ->send();

            return;
        }

        $name = trim((string) ($this->data['name'] ?? ''));
        $departmentId = $this->data['department_id'] ?? null;

        if ($name === '' || blank($departmentId)) {
            Notification::make()
                ->warning()
                ->title('Missing template information')
                ->body('Enter the template name and select a department before generating metadata with AI.')
                ->send();

            return;
        }

        $departmentName = Department::query()
            ->whereKey((int) $departmentId)
            ->value('name');

        if (blank($departmentName)) {
            Notification::make()
                ->danger()
                ->title('Department unavailable')
                ->body('The selected department could not be found.')
                ->send();

            return;
        }

        try {
            $task = AiTask::query()->create([
                'use_case' => AIUseCase::DOCUMENT_DESCRIPTION_GENERATION,
                'status' => AiTaskStatus::PENDING,
                'input' => [
                    'name' => $name,
                    'department_name' => (string) $departmentName,
                ],
                'created_by' => Auth::id(),
            ]);

            $this->metadataAiTaskId = (int) $task->getKey();
            $this->metadataAiTaskPolling = true;
            $this->metadataAiProgress = 0;
            $this->metadataAiCurrentStep = 'Waiting for queue worker';

            ProcessDocumentTemplateMetadataAiJob::dispatch(
                aiTaskId: $task->getKey(),
            );

            Notification::make()
                ->info()
                ->title('AI generation started')
                ->body('Description and document classification are being generated in the background.')
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            $this->resetMetadataAiTaskState();

            Notification::make()
                ->danger()
                ->title('Unable to start AI generation')
                ->body('The AI metadata generation task could not be started.')
                ->send();
        }
    }

    public function refreshMetadataAiTask(): void
    {
        if (
            ! $this->metadataAiTaskPolling
            || $this->metadataAiTaskId === null
        ) {
            return;
        }

        $task = AiTask::query()->find($this->metadataAiTaskId);

        if ($task === null) {
            $this->resetMetadataAiTaskState();

            Notification::make()
                ->danger()
                ->title('AI task unavailable')
                ->body('The AI metadata generation task could not be found.')
                ->send();

            return;
        }

        $this->metadataAiProgress = $task->progress;
        $this->metadataAiCurrentStep = $task->current_step;

        match ($task->status) {
            AiTaskStatus::COMPLETED => $this->applyMetadataAiResult($task),

            AiTaskStatus::FAILED => $this->handleMetadataAiFailure($task),

            AiTaskStatus::CANCELLED => $this->handleMetadataAiCancellation(),

            default => null,
        };
    }

    private function applyMetadataAiResult(AiTask $task): void
    {
        $result = $task->result;

        if (! is_array($result)) {
            $this->handleInvalidMetadataAiResult();

            return;
        }

        $description = $result['description'] ?? null;
        $categoryId = $result['category_id'] ?? null;
        $documentTypeId = $result['document_type_id'] ?? null;
        $regulationTagIds = $result['regulation_tag_ids'] ?? null;

        if (
            ! is_string($description)
            || trim($description) === ''
            || ! is_int($categoryId)
            || ! is_int($documentTypeId)
            || ! is_array($regulationTagIds)
        ) {
            $this->handleInvalidMetadataAiResult();

            return;
        }

        $this->data['description'] = trim($description);
        $this->data['category_id'] = $categoryId;
        $this->data['document_type_id'] = $documentTypeId;
        $this->data['regulationTags'] = array_values(
            $regulationTagIds,
        );

        $this->form->fill($this->data);

        $this->resetMetadataAiTaskState();

        Notification::make()
            ->success()
            ->title('AI generation completed')
            ->body('Description and document classification were generated successfully.')
            ->send();
    }

    private function handleMetadataAiFailure(AiTask $task): void
    {
        $errorMessage = filled($task->error_message)
            ? (string) $task->error_message
            : 'The AI metadata generation task failed.';

        $this->resetMetadataAiTaskState();

        Notification::make()
            ->danger()
            ->title('AI generation failed')
            ->body($errorMessage)
            ->persistent()
            ->send();
    }

    private function handleMetadataAiCancellation(): void
    {
        $this->resetMetadataAiTaskState();

        Notification::make()
            ->warning()
            ->title('AI generation cancelled')
            ->send();
    }

    private function handleInvalidMetadataAiResult(): void
    {
        $this->resetMetadataAiTaskState();

        Notification::make()
            ->danger()
            ->title('Invalid AI result')
            ->body('The AI metadata generation result was incomplete or invalid.')
            ->send();
    }

    private function resetMetadataAiTaskState(): void
    {
        $this->metadataAiTaskId = null;
        $this->metadataAiTaskPolling = false;
        $this->metadataAiProgress = 0;
        $this->metadataAiCurrentStep = null;
    }
}
