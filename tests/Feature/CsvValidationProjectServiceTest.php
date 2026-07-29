<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvExecutionResult;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvSpecificationType;
use App\Domain\QMS\Enums\CsvTestType;
use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Domain\QMS\Models\CsvRequirement;
use App\Domain\QMS\Models\CsvRiskAssessment;
use App\Domain\QMS\Models\CsvSpecification;
use App\Domain\QMS\Models\CsvTestCase;
use App\Domain\QMS\Models\CsvTestExecution;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Services\CsvValidationProjectService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Filament\Resources\CsvValidationProjects\Pages\ViewCsvValidationProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    Permission::findOrCreate('Release:CsvValidationProject', 'web');
    Permission::findOrCreate('View:CsvValidationProject', 'web');
    $this->creator = User::factory()->create();
    $this->executor = User::factory()->create();
    $this->reviewer = User::factory()->create();
    $this->qualityReleaser = User::factory()->create();
    $this->qualityReleaser->givePermissionTo('Release:CsvValidationProject');
});

function releaseReadyCsvProject(
    User $creator,
    User $executor,
    User $reviewer,
): CsvValidationProject {
    $project = CsvValidationProject::factory()->create([
        'status' => CsvValidationProjectStatus::ValidationReview,
        'created_by' => $creator,
        'business_owner_id' => $creator,
        'system_owner_id' => $creator,
        'validation_strategy' => 'Risk-based lifecycle with IQ/OQ/PQ evidence.',
        'release_baseline' => ['application' => '1.0.0', 'configuration' => 'sha256:baseline'],
        'validation_summary' => 'All acceptance criteria passed with no unresolved deviations.',
        'next_periodic_review_date' => today()->addYear(),
    ]);
    $requirement = CsvRequirement::query()->create([
        'csv_validation_project_id' => $project->id,
        'requirement_identifier' => 'URS-001',
        'version' => 1,
        'category' => 'Data integrity',
        'statement' => 'The system shall retain an attributable audit trail.',
        'acceptance_criteria' => 'Audit events include actor, time, reason, and signed decision hash.',
        'criticality' => CsvCriticality::Critical,
        'gxp_relevant' => true,
        'data_integrity_relevant' => true,
        'status' => CsvRequirementStatus::Approved,
        'approved_by' => $reviewer->id,
        'approved_at' => now(),
    ]);
    CsvSpecification::query()->create([
        'csv_validation_project_id' => $project->id,
        'specification_identifier' => 'FS-001',
        'version' => 1,
        'type' => CsvSpecificationType::Functional,
        'title' => 'Audit trail',
        'description' => 'Append-only signed lifecycle events.',
        'status' => CsvRequirementStatus::Approved,
        'approved_by' => $reviewer->id,
        'approved_at' => now(),
    ]);
    CsvRiskAssessment::query()->create([
        'csv_validation_project_id' => $project->id,
        'csv_requirement_id' => $requirement->id,
        'risk_identifier' => 'RA-001',
        'hazard' => 'Undetected record changes',
        'potential_impact' => 'Loss of data integrity',
        'initial_severity' => 5,
        'initial_probability' => 3,
        'initial_detectability' => 3,
        'mitigation' => 'Immutable signed audit events',
        'residual_severity' => 5,
        'residual_probability' => 1,
        'residual_detectability' => 1,
        'acceptance_rationale' => 'Residual RPN is controlled and monitored.',
        'accepted_by' => $reviewer->id,
        'accepted_at' => now(),
    ]);
    $testCase = CsvTestCase::query()->create([
        'csv_validation_project_id' => $project->id,
        'test_identifier' => 'OQ-001',
        'version' => 1,
        'type' => CsvTestType::OperationalQualification,
        'title' => 'Verify audit trail integrity',
        'objective' => 'Demonstrate attributable append-only audit events.',
        'steps' => [['step' => 'Approve a record', 'expected_result' => 'Signed event is retained']],
        'criticality' => CsvCriticality::Critical,
        'status' => CsvRequirementStatus::Approved,
        'approved_by' => $reviewer->id,
        'approved_at' => now(),
    ]);
    $testCase->requirements()->attach($requirement);
    CsvTestExecution::query()->create([
        'csv_validation_project_id' => $project->id,
        'csv_test_case_id' => $testCase->id,
        'execution_no' => 1,
        'environment' => 'Validation',
        'application_version' => '1.0.0',
        'configuration_hash' => hash('sha256', 'baseline'),
        'step_results' => [['step' => 1, 'result' => 'passed', 'actual_result' => 'Signed event retained']],
        'result' => CsvExecutionResult::Passed,
        'actual_result' => 'All expected results met.',
        'evidence_summary' => 'EV-001 screenshot and audit export.',
        'executed_by' => $executor->id,
        'reviewed_by' => $reviewer->id,
        'started_at' => now()->subHour(),
        'completed_at' => now()->subMinutes(30),
        'reviewed_at' => now(),
    ]);

    return $project;
}

it('releases only a complete traceable package with an independent signed QA decision', function (): void {
    $project = releaseReadyCsvProject($this->creator, $this->executor, $this->reviewer);

    $released = app(CsvValidationProjectService::class)->transition(
        $project,
        CsvValidationProjectStatus::Released,
        $this->qualityReleaser,
        'QA confirms the approved baseline is fit for intended GxP use.',
        ipAddress: '203.0.113.20',
        userAgent: 'DocuPharma-CSV-Test/1.0',
    );
    $event = $released->auditEvents()->sole();

    expect($released->status)->toBe(CsvValidationProjectStatus::Released)
        ->and($released->released_by)->toBe($this->qualityReleaser->id)
        ->and($event->signature_hash)->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($event))->toBeTrue()
        ->and(fn () => $event->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('blocks release when a critical requirement lacks independent passing test evidence', function (): void {
    $project = releaseReadyCsvProject($this->creator, $this->executor, $this->reviewer);
    $project->testExecutions()->delete();

    expect(fn () => app(CsvValidationProjectService::class)->transition(
        $project,
        CsvValidationProjectStatus::Released,
        $this->qualityReleaser,
        'Attempt release.',
    ))->toThrow(ValidationException::class);
});

it('blocks a system or business owner from signing the QA release', function (): void {
    $project = releaseReadyCsvProject($this->creator, $this->executor, $this->reviewer);
    $this->creator->givePermissionTo('Release:CsvValidationProject');

    expect(fn () => app(CsvValidationProjectService::class)->transition(
        $project,
        CsvValidationProjectStatus::Released,
        $this->creator,
        'Self approval attempt.',
    ))->toThrow(ValidationException::class);
});

it('enforces independent test review and requires a deviation for failed execution', function (): void {
    $project = releaseReadyCsvProject($this->creator, $this->executor, $this->reviewer);
    $testCase = $project->testCases()->sole();

    expect(fn () => CsvTestExecution::query()->create([
        'csv_validation_project_id' => $project->id,
        'csv_test_case_id' => $testCase->id,
        'execution_no' => 2,
        'environment' => 'Validation',
        'application_version' => '1.0.0',
        'step_results' => [['step' => 1, 'result' => 'passed']],
        'result' => CsvExecutionResult::Passed,
        'executed_by' => $this->executor->id,
        'reviewed_by' => $this->executor->id,
        'started_at' => now()->subHour(),
        'completed_at' => now()->subMinutes(30),
        'reviewed_at' => now(),
    ]))->toThrow(ValidationException::class);

    expect(fn () => CsvTestExecution::query()->create([
        'csv_validation_project_id' => $project->id,
        'csv_test_case_id' => $testCase->id,
        'execution_no' => 3,
        'environment' => 'Validation',
        'application_version' => '1.0.0',
        'step_results' => [['step' => 1, 'result' => 'failed']],
        'result' => CsvExecutionResult::Failed,
        'executed_by' => $this->executor->id,
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]))->toThrow(ValidationException::class);
});

it('locks a reviewed test execution against later alteration', function (): void {
    $project = releaseReadyCsvProject($this->creator, $this->executor, $this->reviewer);
    $execution = $project->testExecutions()->sole();

    expect(fn () => $execution->update(['actual_result' => 'Altered after review.']))
        ->toThrow(LogicException::class);
});

it('shows the release-gate errors instead of silently ignoring QA release', function (): void {
    $this->qualityReleaser->givePermissionTo('View:CsvValidationProject');
    $this->actingAs($this->qualityReleaser);
    $project = CsvValidationProject::factory()->create([
        'status' => CsvValidationProjectStatus::ValidationReview,
        'created_by' => $this->creator,
        'business_owner_id' => $this->creator,
        'system_owner_id' => $this->creator,
        'validation_strategy' => null,
        'release_baseline' => null,
        'validation_summary' => null,
        'next_periodic_review_date' => null,
    ]);

    Livewire::test(ViewCsvValidationProject::class, ['record' => $project->getKey()])
        ->callAction('release', ['reason' => 'QA release review completed.'])
        ->assertActionHalted('release')
        ->assertNotified('QA release blocked');

    expect($project->fresh()?->status)->toBe(CsvValidationProjectStatus::ValidationReview)
        ->and($project->auditEvents()->count())->toBe(0);
});
