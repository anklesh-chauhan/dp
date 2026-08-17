<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\ScheduleMGapItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScheduleMGapItem> */
final class ScheduleMGapItemFactory extends Factory
{
    protected $model = ScheduleMGapItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'assessment_id' => ScheduleMGapAssessment::factory(),
            'part_code' => 'Part_I',
            'clause_ref' => fake()->unique()->bothify('I.##'),
            'clause_title' => fake()->sentence(4),
            'status' => ScheduleMGapItemStatus::NotAssessed,
            'evidence_notes' => null,
            'evidence_document_id' => null,
            'evidence_qms_type' => null,
            'evidence_qms_id' => null,
            'owner_id' => User::factory(),
            'due_at' => null,
            'closed_at' => null,
        ];
    }
}
