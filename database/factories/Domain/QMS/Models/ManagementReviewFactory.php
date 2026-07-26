<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ManagementReviewInputType;
use App\Domain\QMS\Enums\ManagementReviewStatus;
use App\Domain\QMS\Enums\ManagementReviewType;
use App\Domain\QMS\Models\ManagementReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ManagementReview> */
final class ManagementReviewFactory extends Factory
{
    protected $model = ManagementReview::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $periodEnd = today()->subMonth()->endOfMonth();

        return [
            'type' => ManagementReviewType::Annual,
            'status' => ManagementReviewStatus::Draft,
            'title' => fake()->sentence(5),
            'period_start_at' => $periodEnd->copy()->subYear()->addDay(),
            'period_end_at' => $periodEnd,
            'scheduled_at' => today()->addDays(30)->setTime(10, 0),
            'held_at' => null,
            'chair_id' => User::factory(),
            'coordinator_id' => User::factory(),
            'created_by' => User::factory(),
            'approved_by' => null,
            'required_inputs' => array_map(
                static fn (ManagementReviewInputType $type): string => $type->value,
                ManagementReviewInputType::cases(),
            ),
            'input_summary' => null,
            'decisions' => null,
            'action_summary' => null,
            'minutes_issued_at' => null,
            'approved_at' => null,
            'completed_at' => null,
        ];
    }
}
