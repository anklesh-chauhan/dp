<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocument;
use App\Models\DocumentIssuance;
use App\Models\IssuanceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentIssuance>
 */
class DocumentIssuanceFactory extends Factory
{
    protected $model = DocumentIssuance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => ControlledDocument::factory(),
            'copy_number' => fake()->unique()->numberBetween(1, 9999),
            'issuance_number' => fake()->unique()->bothify('DOC-#####-C##'),
            'issuance_type' => DocumentIssuance::TYPE_REFERENCE,
            'issued_by' => User::factory(),
            'issued_at' => now(),
            'issuance_status_id' => IssuanceStatus::idFor(IssuanceStatus::ACTIVE),
            'watermark_code' => fake()->unique()->bothify('CC-########'),
        ];
    }
}
