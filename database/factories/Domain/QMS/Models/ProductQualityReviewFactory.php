<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ProductQualityReviewStatus;
use App\Domain\QMS\Enums\ProductQualityReviewType;
use App\Domain\QMS\Models\ProductQualityReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductQualityReview> */
final class ProductQualityReviewFactory extends Factory
{
    protected $model = ProductQualityReview::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $periodEnd = today()->subMonth()->endOfMonth();

        return [
            'type' => ProductQualityReviewType::Annual,
            'status' => ProductQualityReviewStatus::Draft,
            'title' => fake()->sentence(5),
            'product_name' => fake()->words(2, true),
            'product_code' => fake()->optional()->bothify('PRD-####'),
            'dosage_form' => fake()->optional()->randomElement(['Tablet', 'Capsule', 'Injection', 'Suspension']),
            'period_start_at' => $periodEnd->copy()->subYear()->addDay(),
            'period_end_at' => $periodEnd,
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'input_summary' => null,
            'conclusions' => null,
            'recommendations' => null,
            'yield_summary' => null,
            'reject_summary' => null,
            'started_at' => null,
            'approved_at' => null,
            'closed_at' => null,
        ];
    }
}
