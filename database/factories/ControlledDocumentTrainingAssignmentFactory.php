<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\TMS\Enums\TrainingAssignmentSource;
use App\Domain\TMS\Models\TrainingAssignment;
use App\Models\ControlledDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingAssignment>
 */
class ControlledDocumentTrainingAssignmentFactory extends Factory
{
    protected $model = TrainingAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_type' => TrainingAssignmentSource::ControlledDocument,
            'controlled_document_id' => ControlledDocument::factory(),
            'training_program_id' => null,
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'completed_at' => null,
            'completion_comments' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'completed_at' => now(),
            'completion_comments' => 'I have read and understood this document.',
        ]);
    }
}
