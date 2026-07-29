<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocument;
use App\Models\ControlledDocumentSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentSection>
 */
class ControlledDocumentSectionFactory extends Factory
{
    protected $model = ControlledDocumentSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => ControlledDocument::factory(),
            'title' => fake()->randomElement(['Purpose', 'Scope', 'Procedure']),
            'section_order' => fake()->numberBetween(1, 8),
            'content' => '<p>'.fake()->paragraph().'</p>',
        ];
    }
}
