<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\DMS\Enums\TemplateApprovalStatus;
use App\Models\SopTemplateApprovalEvent;
use App\Models\SopTemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SopTemplateApprovalEvent>
 */
class SopTemplateApprovalEventFactory extends Factory
{
    protected $model = SopTemplateApprovalEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sop_template_version_id' => SopTemplateVersion::factory(),
            'event_uuid' => (string) Str::uuid(),
            'from_status' => TemplateApprovalStatus::Draft,
            'to_status' => TemplateApprovalStatus::Submitted,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'occurred_at' => now(),
        ];
    }
}
