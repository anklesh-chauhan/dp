<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\ChangeControlAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChangeControlAuditEvent>
 */
class ChangeControlAuditEventFactory extends Factory
{
    protected $model = ChangeControlAuditEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'change_control_id' => ChangeControl::factory(),
            'event_uuid' => (string) Str::uuid(),
            'from_status' => ChangeControlStatus::Draft,
            'to_status' => ChangeControlStatus::Submitted,
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
