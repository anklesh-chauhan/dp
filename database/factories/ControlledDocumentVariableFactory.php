<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocument;
use App\Models\ControlledDocumentVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentVariable>
 */
class ControlledDocumentVariableFactory extends Factory
{
    protected $model = ControlledDocumentVariable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => ControlledDocument::factory(),
            'variable_name' => fake()->slug(2),
            'value' => fake()->word(),
        ];
    }
}
