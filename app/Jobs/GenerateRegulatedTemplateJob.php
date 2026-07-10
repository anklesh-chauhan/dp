<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SopTemplate;
use App\Models\TemplateStatus;
use App\Models\VariableDataType;
use App\Services\AI\TemplateGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateRegulatedTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Set a high timeout for this job specifically (e.g., 10 minutes)
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SopTemplate $template,
        public string $regulationTags
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TemplateGeneratorService $aiService): void
    {
        // Checkpoint 1: Initialized
        $this->template->update([
            'generation_status' => 'Connecting to AI service (Gemini, with Ollama fallback)...',
            'generation_progress' => 15,
        ]);

        // 1. Call your Ollama Service
        $result = $aiService->generateRegulatedTemplate($this->template->toArray(), $this->regulationTags);

        if (! $result) {
            $this->template->update([
                'generation_status' => SopTemplate::GENERATION_STATUS_FAILED,
                'generation_progress' => 0,
            ]);

            return;
        }

        // Checkpoint 2: Model response received, parsing structures
        $this->template->update([
            'generation_status' => 'Structuring compliance sections and layout elements...',
            'generation_progress' => 50,
        ]);

        // 2. Perform your Postgres transaction
        DB::transaction(function () use ($result) {
            // Initialize version 1
            $version = $this->template->versions()->updateOrCreate(
                ['version' => 1,],
                [
                'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
                'change_reason' => 'Auto-generated base boilerplate compliant with specified regulation tags.',
                'created_by' => $this->template->created_by,
            ]);

            $this->template->update(['current_version' => 1]);

            // Save sections
            foreach ($result['sections'] as $section) {
                $version->sections()->create([
                    'title' => $section['title'],
                    'section_order' => $section['section_order'],
                    'section_type' => $section['section_type'],
                    'content' => $section['content'],
                    'is_required' => true,
                ]);
            }

            // Checkpoint 3: Sections created, generating variables
            $this->template->update([
                'generation_status' => 'Generating variable definitions...',
                'generation_progress' => 75,
            ]);

            // 1. Fetch the exact code-to-ID map from your Postgres table
            $dataTypeMap = VariableDataType::pluck('id', 'code')->toArray();

            foreach ($result['variables'] as $variable) {
                $stringCode = strtolower($variable['datatype']); // Force lowercase to avoid casing mismatch

                // Find the ID, or look up the explicit ID for 'text'
                $dataTypeId = $dataTypeMap[$stringCode] ?? $dataTypeMap['text'] ?? null;

                // 2. Create the record
                $version->variables()->create([
                    'name' => $variable['name'],
                    'label' => $variable['label'],
                    'variable_data_type_id' => $dataTypeId,
                    'default_value' => $variable['default_value'],
                    'required' => $variable['required'] ? true : false,
                ]);
            }

            $this->template->update([
                'generation_status' => 'Finalizing template generation...',
                'generation_progress' => 95,
            ]);

            $this->template->update([
                'generation_status' => SopTemplate::GENERATION_STATUS_COMPLETED,
                'generation_progress' => 100,
            ]);
        });
    }
}
