<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\DocumentCategory;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\TemplateStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplate>
 */
class DocumentTemplateFactory extends Factory
{
    protected $model = DocumentTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'code' => 'TPL-'.fake()->unique()->bothify('??-####'),
            'description' => fake()->paragraph(),
            'department_id' => Department::factory(),
            'category_id' => DocumentCategory::factory(),
            'document_type_id' => DocumentType::factory(),
            'template_status_id' => TemplateStatus::idFor(TemplateStatus::DRAFT),
            'current_version' => 0,
        ];
    }
}
