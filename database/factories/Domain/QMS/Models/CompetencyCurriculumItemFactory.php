<?php

declare(strict_types=1);

namespace Database\Factories\Domain\QMS\Models;

use App\Domain\QMS\Models\CompetencyCurriculum;
use App\Domain\QMS\Models\CompetencyCurriculumItem;
use App\Models\ControlledDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompetencyCurriculumItem> */
final class CompetencyCurriculumItemFactory extends Factory
{
    protected $model = CompetencyCurriculumItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'curriculum_id' => CompetencyCurriculum::factory(),
            'controlled_document_id' => ControlledDocument::factory(),
            'is_required' => true,
            'sort_order' => fake()->numberBetween(0, 20),
        ];
    }
}
