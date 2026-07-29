<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocument;
use App\Models\Department;
use App\Models\DocumentStatus;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ControlledDocument>
 */
class ControlledDocumentFactory extends Factory
{
    protected $model = ControlledDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_series_id' => (string) Str::uuid(),
            'template_id' => DocumentTemplate::factory(),
            'template_version_id' => DocumentTemplateVersion::factory(),
            'document_number' => 'SOP-QA-'.fake()->unique()->numerify('#####'),
            'title' => fake()->sentence(4),
            'version' => 1,
            'department_id' => Department::factory(),
            'document_status_id' => DocumentStatus::idFor(DocumentStatus::DRAFT),
            'effective_date' => now()->addDays(7)->toDateString(),
            'review_date' => now()->addYear()->toDateString(),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
