<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SopDocument;
use App\Models\SopDocumentVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SopDocumentVariable>
 */
class SopDocumentVariableFactory extends Factory
{
    protected $model = SopDocumentVariable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => SopDocument::factory(),
            'variable_name' => fake()->slug(2),
            'value' => fake()->word(),
        ];
    }
}
