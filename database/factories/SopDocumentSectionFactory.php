<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SopDocument;
use App\Models\SopDocumentSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopDocumentSection>
 */
class SopDocumentSectionFactory extends Factory
{
    protected $model = SopDocumentSection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => SopDocument::factory(),
            'title' => fake()->randomElement(['Purpose', 'Scope', 'Procedure']),
            'section_order' => fake()->numberBetween(1, 8),
            'content' => '<p>'.fake()->paragraph().'</p>',
        ];
    }
}
