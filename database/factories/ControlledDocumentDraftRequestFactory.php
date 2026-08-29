<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ControlledDocumentDraftRequest;
use App\Models\ControlledDocumentDraftSession;
use App\Models\User;
use App\Services\AI\Enums\ControlledDocumentDraftRequestStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ControlledDocumentDraftRequest>
 */
class ControlledDocumentDraftRequestFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'controlled_document_draft_session_id' => ControlledDocumentDraftSession::factory(),
            'requested_by' => User::factory(),
            'message' => fake()->sentence(),
            'status' => ControlledDocumentDraftRequestStatus::QUEUED,
            'queued_at' => now(),
        ];
    }
}
