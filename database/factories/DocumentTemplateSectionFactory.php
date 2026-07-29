<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentTemplateSection;
use App\Models\DocumentTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentTemplateSection>
 */
class DocumentTemplateSectionFactory extends Factory
{
    protected $model = DocumentTemplateSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_version_id' => DocumentTemplateVersion::factory(),
            'title' => fake()->randomElement(['Purpose', 'Scope', 'Responsibility', 'Procedure']),
            'section_order' => fake()->numberBetween(1, 8),
            'section_type' => 'rich_text',
            'content' => '<p>'.fake()->paragraph().' {{department}}</p>',
            'is_required' => true,
        ];
    }
}
