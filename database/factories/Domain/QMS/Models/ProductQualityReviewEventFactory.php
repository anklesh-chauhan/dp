<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Domain\QMS\Models\ProductQualityReviewEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProductQualityReviewEvent> */
final class ProductQualityReviewEventFactory extends Factory
{
    protected $model = ProductQualityReviewEvent::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'product_quality_review_id' => ProductQualityReview::factory(),
            'from_status' => ProductQualityReviewStatus::Draft,
            'to_status' => ProductQualityReviewStatus::InProgress,
            'actor_id' => User::factory(),
            'reason' => fake()->sentence(),
            'context' => [],
            'occurred_at' => now(),
        ];
    }
}
