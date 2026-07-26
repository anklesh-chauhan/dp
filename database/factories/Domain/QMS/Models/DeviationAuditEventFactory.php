<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\DeviationAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DeviationAuditEvent>
 */
class DeviationAuditEventFactory extends Factory
{
    protected $model = DeviationAuditEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'deviation_id' => Deviation::factory(),
            'from_status' => DeviationStatus::Draft,
            'to_status' => DeviationStatus::Open,
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
