<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Models\RiskAssessment;
use App\Domain\QMS\Models\RiskAssessmentEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<RiskAssessmentEvent> */
final class RiskAssessmentEventFactory extends Factory
{
    protected $model = RiskAssessmentEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'risk_assessment_id' => RiskAssessment::factory(),
            'from_status' => RiskAssessmentStatus::Draft,
            'to_status' => RiskAssessmentStatus::InReview,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
