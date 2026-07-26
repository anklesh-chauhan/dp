<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Models\ManagementReview;
use App\Domain\QMS\Models\ManagementReviewEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ManagementReviewEvent> */
final class ManagementReviewEventFactory extends Factory
{
    protected $model = ManagementReviewEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'management_review_id' => ManagementReview::factory(),
            'from_status' => ManagementReviewStatus::Draft,
            'to_status' => ManagementReviewStatus::Scheduled,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
