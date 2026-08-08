<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentExecution;
use App\Models\DocumentExecutionMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentExecutionMaterial>
 */
class DocumentExecutionMaterialFactory extends Factory
{
    protected $model = DocumentExecutionMaterial::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_execution_id' => DocumentExecution::factory(),
            'material_order' => fake()->numberBetween(1, 10),
            'material_name' => fake()->words(3, true),
            'material_code' => fake()->bothify('MAT-####'),
            'planned_quantity' => 10,
            'actual_quantity' => 10,
            'unit' => 'kg',
            'status' => 'reconciled',
        ];
    }
}
