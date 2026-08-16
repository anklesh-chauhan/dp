<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocument;
use App\Models\ControlledDocumentTrainingAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentTrainingAssignment>
 */
class ControlledDocumentTrainingAssignmentFactory extends Factory
{
    protected $model = ControlledDocumentTrainingAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => ControlledDocument::factory(),
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
