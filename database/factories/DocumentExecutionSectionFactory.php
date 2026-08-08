<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentExecution;
use App\Models\DocumentExecutionSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentExecutionSection>
 */
class DocumentExecutionSectionFactory extends Factory
{
    protected $model = DocumentExecutionSection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_execution_id' => DocumentExecution::factory(),
            'title' => fake()->sentence(3),
            'section_order' => fake()->numberBetween(1, 10),
            'section_type' => 'rich_text',
            'status' => 'pending',
        ];
    }
}
