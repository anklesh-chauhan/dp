<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Department;
use App\Models\SopDocument;
use App\Models\SopTemplate;
use App\Models\SopTemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopDocument>
 */
class SopDocumentFactory extends Factory
{
    protected $model = SopDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => SopTemplate::factory(),
            'template_version_id' => SopTemplateVersion::factory(),
            'document_number' => 'SOP-QA-'.fake()->unique()->numerify('#####'),
            'title' => fake()->sentence(4),
            'version' => 1,
            'department_id' => Department::factory(),
            'status' => DocumentStatus::Draft,
            'effective_date' => now()->addDays(7)->toDateString(),
            'review_date' => now()->addYear()->toDateString(),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
        ];
    }
}
