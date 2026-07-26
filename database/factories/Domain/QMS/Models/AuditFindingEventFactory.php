<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\AuditFindingDisposition;
use App\Domain\QMS\Models\AuditFinding;
use App\Domain\QMS\Models\AuditFindingEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuditFindingEvent> */
final class AuditFindingEventFactory extends Factory
{
    protected $model = AuditFindingEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'audit_finding_id' => AuditFinding::factory(),
            'from_disposition' => AuditFindingDisposition::Open,
            'to_disposition' => AuditFindingDisposition::ResponsePending,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
