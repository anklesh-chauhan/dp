<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\TMS\Models\CompetencyCurriculum;
use Illuminate\Database\Seeder;

final class CompetencyCurriculumSeeder extends Seeder
{
    public function run(): void
    {
        CompetencyCurriculum::query()->updateOrCreate(
            ['code' => 'GMP_EXECUTION'],
            [
                'name' => 'GMP document execution QA',
                'role_name' => 'QA Approver',
                'description' => 'Competency required before QA approval of document executions. Active by default; the gate remains fail-open until required SOPs are linked.',
                'requalification_months' => 12,
                'gate_key' => CompetencyCurriculum::GATE_DOCUMENT_EXECUTION_QA,
                'is_active' => true,
            ],
        );

        CompetencyCurriculum::query()->updateOrCreate(
            ['code' => 'CHANGE_CONTROL_APPROVE'],
            [
                'name' => 'Change control approval',
                'role_name' => 'Change Control Approver',
                'description' => 'Competency required before approving change controls. Active by default; the gate remains fail-open until required SOPs are linked.',
                'requalification_months' => 12,
                'gate_key' => CompetencyCurriculum::GATE_CHANGE_CONTROL_APPROVE,
                'is_active' => true,
            ],
        );
    }
}
