<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\RiskAssessmentStatus;
use App\Domain\QMS\Enums\RiskAssessmentType;
use App\Domain\QMS\Models\RiskAssessment;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('installs the dormant risk assessment scoring and mitigation schema', function (): void {
    expect(Schema::hasColumns('risk_assessments', [
        'risk_number', 'type', 'status', 'title', 'scope', 'hazard', 'potential_harm',
        'existing_controls', 'department_id', 'owner_id', 'created_by', 'approver_id',
        'initial_severity', 'initial_probability', 'initial_detectability', 'mitigation_plan',
        'mitigation_due_at', 'mitigation_completed_at', 'residual_severity',
        'residual_probability', 'residual_detectability', 'review_due_at', 'approved_at',
        'closed_at',
    ]))->toBeTrue();
});

it('persists risk traceability attribution initial residual scoring and milestones', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create();
    $creator = User::factory()->create();
    $approver = User::factory()->create();

    $assessment = RiskAssessment::factory()->create([
        'type' => RiskAssessmentType::ComputerizedSystem,
        'status' => RiskAssessmentStatus::Monitoring,
        'department_id' => $department,
        'owner_id' => $owner,
        'created_by' => $creator,
        'approver_id' => $approver,
        'initial_severity' => 5,
        'initial_probability' => 4,
        'initial_detectability' => 3,
        'mitigation_plan' => 'Implement immutable audit trails and periodic access review.',
        'mitigation_completed_at' => '2026-09-01 15:00:00',
        'residual_severity' => 4,
        'residual_probability' => 2,
        'residual_detectability' => 1,
        'approved_at' => '2026-08-01 10:00:00',
        'review_due_at' => '2026-12-01',
    ])->refresh();

    expect($assessment->risk_number)->toStartWith('RA-')
        ->and($assessment->type)->toBe(RiskAssessmentType::ComputerizedSystem)
        ->and($assessment->status)->toBe(RiskAssessmentStatus::Monitoring)
        ->and($assessment->department?->is($department))->toBeTrue()
        ->and($assessment->owner?->is($owner))->toBeTrue()
        ->and($assessment->creator?->is($creator))->toBeTrue()
        ->and($assessment->approver?->is($approver))->toBeTrue()
        ->and($assessment->initialRiskPriorityNumber())->toBe(60)
        ->and($assessment->residualRiskPriorityNumber())->toBe(8)
        ->and($assessment->mitigation_completed_at?->format('Y-m-d H:i:s'))->toBe('2026-09-01 15:00:00')
        ->and($assessment->review_due_at?->toDateString())->toBe('2026-12-01');
});

it('keeps residual risk unknown until every residual factor is assessed', function (): void {
    $assessment = RiskAssessment::factory()->create([
        'residual_severity' => 2,
        'residual_probability' => null,
        'residual_detectability' => 1,
    ]);

    expect($assessment->residualRiskPriorityNumber())->toBeNull();
});

it('owns risk assessment permissions without exposing an incomplete resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:RiskAssessment',
            'View:RiskAssessment',
            'Create:RiskAssessment',
            'Update:RiskAssessment',
            'Review:RiskAssessment',
            'Approve:RiskAssessment',
            'Mitigate:RiskAssessment',
            'Monitor:RiskAssessment',
            'Close:RiskAssessment',
            'Manage:RiskAssessment',
        )
        ->and(class_exists('App\\Filament\\Resources\\RiskAssessments\\RiskAssessmentResource'))
        ->toBeFalse();
});
