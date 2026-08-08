<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DocumentExecution;
use App\Models\DocumentIssuance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentExecution>
 */
class DocumentExecutionFactory extends Factory
{
    protected $model = DocumentExecution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_issuance_id' => DocumentIssuance::factory()->state(['issuance_type' => DocumentIssuance::TYPE_EXECUTION]),
            'execution_number' => fake()->unique()->bothify('REC-#####-C##'),
            'document_number' => fake()->bothify('DOC-#####'),
            'document_version' => 1,
            'document_type_code' => 'FORM',
            'workflow_configuration' => [],
            'status' => DocumentExecution::STATUS_ISSUED,
            'disposition' => DocumentExecution::DISPOSITION_NOT_APPLICABLE,
        ];
    }
}
