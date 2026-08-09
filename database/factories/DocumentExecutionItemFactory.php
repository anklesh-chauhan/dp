<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentExecutionItem;
use App\Models\DocumentExecutionSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentExecutionItem>
 */
class DocumentExecutionItemFactory extends Factory
{
    protected $model = DocumentExecutionItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_execution_section_id' => DocumentExecutionSection::factory(),
            'item_order' => fake()->numberBetween(1, 10),
            'row_number' => 1,
            'label' => fake()->sentence(4),
            'value_type' => 'text',
            'is_required' => true,
        ];
    }
}
