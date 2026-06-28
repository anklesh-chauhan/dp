<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SopTemplateSection;
use App\Models\SopTemplateVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopTemplateSection>
 */
class SopTemplateSectionFactory extends Factory
{
    protected $model = SopTemplateSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_version_id' => SopTemplateVersion::factory(),
            'title' => fake()->randomElement(['Purpose', 'Scope', 'Responsibility', 'Procedure']),
            'section_order' => fake()->numberBetween(1, 8),
            'section_type' => 'rich_text',
            'content' => '<p>'.fake()->paragraph().' {{department}}</p>',
            'is_required' => true,
        ];
    }
}
