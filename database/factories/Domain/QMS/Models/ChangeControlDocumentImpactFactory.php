<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\DocumentImpactAction;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\ChangeControlDocumentImpact;
use App\Models\ControlledDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangeControlDocumentImpact>
 */
class ChangeControlDocumentImpactFactory extends Factory
{
    protected $model = ChangeControlDocumentImpact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'change_control_id' => ChangeControl::factory(),
            'source_document_id' => ControlledDocument::factory(),
            'result_document_id' => null,
            'required_action' => DocumentImpactAction::Revise,
            'rationale' => fake()->sentence(),
        ];
    }
}
