<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Models\DocumentTemplateApprovalEvent;
use App\Models\DocumentTemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentTemplateApprovalEvent>
 */
class DocumentTemplateApprovalEventFactory extends Factory
{
    protected $model = DocumentTemplateApprovalEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_template_version_id' => DocumentTemplateVersion::factory(),
            'event_uuid' => (string) Str::uuid(),
            'from_status' => TemplateApprovalStatus::Draft,
            'to_status' => TemplateApprovalStatus::Submitted,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }
}
