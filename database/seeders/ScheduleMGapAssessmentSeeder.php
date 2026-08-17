<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\QMS\Enums\ScheduleMGapItemStatus;
use App\Domain\QMS\Models\ScheduleMGapAssessment;
use App\Domain\QMS\Models\ScheduleMGapItem;
use Illuminate\Database\Seeder;

class ScheduleMGapAssessmentSeeder extends Seeder
{
    /**
     * Seed the default Schedule M Part I clause checklist onto an assessment.
     */
    public function seedPartIChecklist(ScheduleMGapAssessment $assessment): void
    {
        foreach (ScheduleMGapAssessment::partIClauseDefinitions() as $clause) {
            ScheduleMGapItem::query()->firstOrCreate(
                [
                    'assessment_id' => $assessment->getKey(),
                    'part_code' => $clause['part_code'],
                    'clause_ref' => $clause['clause_ref'],
                ],
                [
                    'clause_title' => $clause['clause_title'],
                    'status' => ScheduleMGapItemStatus::NotAssessed,
                    'owner_id' => $assessment->owner_id,
                ],
            );
        }
    }

    public function run(): void
    {
        // No default assessment rows — checklist is applied via factory state or seedPartIChecklist().
    }
}
