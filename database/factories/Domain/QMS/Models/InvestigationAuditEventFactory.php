<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\InvestigationStatus;
use App\Domain\QMS\Models\Investigation;
use App\Domain\QMS\Models\InvestigationAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvestigationAuditEvent>
 */
class InvestigationAuditEventFactory extends Factory
{
    protected $model = InvestigationAuditEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'investigation_id' => Investigation::factory(),
            'from_status' => InvestigationStatus::Draft,
            'to_status' => InvestigationStatus::InProgress,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'signature_hash' => null,
            'signature_ip_address' => null,
            'signature_user_agent' => null,
            'occurred_at' => now(),
        ];
    }
}
