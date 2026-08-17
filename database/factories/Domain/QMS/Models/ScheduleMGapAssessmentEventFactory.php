<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\ScheduleMGapAssessmentEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ScheduleMGapAssessmentEvent> */
final class ScheduleMGapAssessmentEventFactory extends Factory
{
    protected $model = ScheduleMGapAssessmentEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'schedule_m_gap_assessment_id' => ScheduleMGapAssessment::factory(),
            'from_status' => ScheduleMGapAssessmentStatus::Draft,
            'to_status' => ScheduleMGapAssessmentStatus::InProgress,
            'event_type' => 'transition',
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
