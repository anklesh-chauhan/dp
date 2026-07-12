<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\Department;
use App\Services\AI\DocumentAiDescriptionGenerator;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Log;

trait GeneratesSopTemplateDescriptionFromMetadata
{
    private bool $isGeneratingDescriptionFromMetadata = false;

    public function generateDescriptionFromMetadata(Set $set): void
    {
        if ($this->isGeneratingDescriptionFromMetadata) {
            return;
        }

        $name = trim((string) ($this->data['name'] ?? ''));
        $departmentId = $this->data['department_id'] ?? null;

        if ($name === '' || blank($departmentId)) {
            return;
        }

        $departmentName = Department::query()
            ->whereKey((int) $departmentId)
            ->value('name');

        if (blank($departmentName)) {
            return;
        }

        $this->isGeneratingDescriptionFromMetadata = true;

        try {
            $result = app(DocumentAiDescriptionGenerator::class)->generate(
                name: $name,
                departmentName: (string) $departmentName,
            );

            $description = $result['description'] ?? null;

            if (blank($description)) {
                return;
            }

            // 1. Set the description field state
            $set('description', $description);

            // 2. Synchronize the newly generated state with Filament's internal Livewire data array
            $this->data['description'] = $description;

            // 3. Chain execution immediately to re-classify document fields based on the new description
            if (method_exists($this, 'classifyFromMetadata')) {
                $this->classifyFromMetadata($set);
            }
        } catch (\Throwable $exception) {
            Log::error(
                'Failed to generate description trace from Livewire context.',
                ['exception' => $exception]
            );
        } finally {
            $this->isGeneratingDescriptionFromMetadata = false;
        }
    }
}
