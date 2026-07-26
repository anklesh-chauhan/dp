<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\InternalAuditStatus;
use App\Domain\QMS\Models\InternalAudit;
use App\Domain\QMS\Models\InternalAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<InternalAuditEvent> */
final class InternalAuditEventFactory extends Factory
{
    protected $model = InternalAuditEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'internal_audit_id' => InternalAudit::factory(),
            'from_status' => InternalAuditStatus::Draft,
            'to_status' => InternalAuditStatus::Scheduled,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
