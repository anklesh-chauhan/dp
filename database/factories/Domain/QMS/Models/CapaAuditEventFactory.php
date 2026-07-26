<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\CapaStatus;
use App\Domain\QMS\Models\Capa;
use App\Domain\QMS\Models\CapaAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CapaAuditEvent> */
final class CapaAuditEventFactory extends Factory
{
    protected $model = CapaAuditEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'capa_id' => Capa::factory(),
            'from_status' => CapaStatus::Draft,
            'to_status' => CapaStatus::Planned,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
