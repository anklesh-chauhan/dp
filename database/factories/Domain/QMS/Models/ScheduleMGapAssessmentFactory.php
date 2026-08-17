<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ScheduleMGapAssessmentStatus;
use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\ScheduleMGapItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScheduleMGapAssessment> */
final class ScheduleMGapAssessmentFactory extends Factory
{
    protected $model = ScheduleMGapAssessment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'status' => ScheduleMGapAssessmentStatus::Draft,
            'site_name' => fake()->company().' Manufacturing Site',
            'period_label' => fake()->optional()->bothify('CY####'),
            'owner_id' => User::factory(),
            'created_by' => User::factory(),
            'approved_at' => null,
            'closed_at' => null,
        ];
    }

    public function withPartIChecklist(?User $itemOwner = null): static
    {
        return $this->afterCreating(function (ScheduleMGapAssessment $assessment) use ($itemOwner): void {
            foreach (ScheduleMGapAssessment::partIClauseDefinitions() as $clause) {
                ScheduleMGapItem::query()->create([
                    'assessment_id' => $assessment->getKey(),
                    'part_code' => $clause['part_code'],
                    'clause_ref' => $clause['clause_ref'],
                    'clause_title' => $clause['clause_title'],
                    'status' => ScheduleMGapItemStatus::NotAssessed,
                    'owner_id' => $itemOwner?->getKey() ?? $assessment->owner_id,
                ]);
            }
        });
    }
}
