<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocumentSection;
use App\Models\ControlledDocumentSectionTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentSectionTable>
 */
class ControlledDocumentSectionTableFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'section_id' => ControlledDocumentSection::factory(),
            'title' => fake()->words(3, true),
            'table_order' => 1,
            'execution_layout' => 'table',
            'row_count' => 1,
        ];
    }
}
