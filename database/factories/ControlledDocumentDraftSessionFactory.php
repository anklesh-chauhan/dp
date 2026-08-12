<?php

namespace Database\Factories;

use App\Models\ControlledDocumentDraftSession;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\User;
use App\Services\AI\Enums\ControlledDocumentDraftSessionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentDraftSession>
 */
class ControlledDocumentDraftSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'created_by' => User::factory(),
            'template_id' => DocumentTemplate::factory(),
            'template_version_id' => DocumentTemplateVersion::factory(),
            'owner_id' => User::factory(),
            'status' => ControlledDocumentDraftSessionStatus::GATHERING,
            'brief' => [],
            'draft_variables' => [],
            'preview_revision' => 0,
        ];
    }
}
