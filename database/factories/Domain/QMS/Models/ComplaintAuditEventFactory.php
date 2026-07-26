<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ComplaintStatus;
use App\Domain\QMS\Models\Complaint;
use App\Domain\QMS\Models\ComplaintAuditEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ComplaintAuditEvent> */
final class ComplaintAuditEventFactory extends Factory
{
    protected $model = ComplaintAuditEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'complaint_id' => Complaint::factory(),
            'from_status' => ComplaintStatus::Draft,
            'to_status' => ComplaintStatus::Received,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
