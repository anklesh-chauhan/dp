<?php

namespace Database\Factories;

use App\Models\ControlledDocument;
use App\Models\ControlledDocumentAccessGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentAccessGrant>
 */
class ControlledDocumentAccessGrantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'controlled_document_id' => ControlledDocument::factory(),
            'user_id' => User::factory(),
            'can_view' => true,
            'can_print' => false,
            'can_download' => false,
            'expires_at' => null,
            'granted_by' => User::factory(),
        ];
    }
}
