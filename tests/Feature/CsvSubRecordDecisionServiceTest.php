<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\CsvCriticality;
use App\Domain\QMS\Enums\CsvExecutionResult;
use App\Domain\QMS\Enums\CsvRequirementStatus;
use App\Domain\QMS\Enums\CsvSpecificationType;
use App\Domain\QMS\Enums\CsvTestType;
use App\Domain\QMS\Enums\CsvValidationProjectStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\CsvRequirement;
use App\Domain\QMS\Models\CsvRiskAssessment;
use App\Domain\QMS\Models\CsvSpecification;
use App\Domain\QMS\Models\CsvTestCase;
use App\Domain\QMS\Models\CsvTestExecution;
use App\Domain\QMS\Models\CsvValidationProject;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Services\CsvRequirementApprovalService;
use App\Domain\QMS\Services\CsvRiskAcceptanceService;
use App\Domain\QMS\Services\CsvSpecificationApprovalService;
use App\Domain\QMS\Services\CsvTestCaseApprovalService;
use App\Domain\QMS\Services\CsvTestExecutionReviewService;
use App\Domain\QMS\Services\CsvValidationProjectService;
use App\Domain\Shared\Contracts\ElectronicSignatureVerifier;
use App\Exceptions\ModuleNotEnabledException;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    foreach ([
        'Specify:CsvValidationProject',
        'Test:CsvValidationProject',
        'Review:CsvValidationProject',
        'Release:CsvValidationProject',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->approver = User::factory()->create();
    $this->approver->givePermissionTo([
        'Specify:CsvValidationProject',
        'Test:CsvValidationProject',
        'Review:CsvValidationProject',
    ]);
    $this->executor = User::factory()->create();
    $this->reviewer = User::factory()->create();
    $this->reviewer->givePermissionTo('Review:CsvValidationProject');
});

function draftCsvProject(): CsvValidationProject
{
    return CsvValidationProject::factory()->create([
        'status' => CsvValidationProjectStatus::Testing,
    ]);
}

function draftRequirement(CsvValidationProject $project): CsvRequirement
{
    return CsvRequirement::query()->create([
        'csv_validation_project_id' => $project->id,
        'requirement_identifier' => 'URS-'.Str::upper(Str::random(4)),
        'version' => 1,
        'category' => 'Functional',
        'statement' => 'System shall retain attributable records.',
        'acceptance_criteria' => 'Audit trail includes actor and reason.',
        'criticality' => CsvCriticality::High,
        'gxp_relevant' => true,
        'status' => CsvRequirementStatus::Draft,
    ]);
}

function draftSpecification(CsvValidationProject $project): CsvSpecification
{
    return CsvSpecification::query()->create([
        'csv_validation_project_id' => $project->id,
        'specification_identifier' => 'FS-'.Str::upper(Str::random(4)),
        'version' => 1,
        'type' => CsvSpecificationType::Functional,
        'title' => 'Functional specification',
        'description' => 'Describes intended system behavior.',
        'status' => CsvRequirementStatus::Draft,
    ]);
}

function draftTestCase(CsvValidationProject $project): CsvTestCase
{
    return CsvTestCase::query()->create([
        'csv_validation_project_id' => $project->id,
        'test_identifier' => 'OQ-'.Str::upper(Str::random(4)),
        'version' => 1,
        'type' => CsvTestType::OperationalQualification,
        'title' => 'Operational qualification',
        'objective' => 'Demonstrate intended use.',
        'steps' => [['step' => 'Execute', 'expected_result' => 'Pass']],
        'criticality' => CsvCriticality::High,
        'status' => CsvRequirementStatus::Draft,
    ]);
}

function completedExecution(
    CsvValidationProject $project,
    CsvTestCase $testCase,
    User $executor,
    CsvExecutionResult $result = CsvExecutionResult::Passed,
    ?Deviation $deviation = null,
): CsvTestExecution {
    return CsvTestExecution::query()->create([
        'csv_validation_project_id' => $project->id,
        'csv_test_case_id' => $testCase->id,
        'execution_no' => 1,
        'environment' => 'Validation',
        'application_version' => '1.0.0',
        'step_results' => [['step' => 1, 'result' => $result->value, 'actual_result' => 'Observed']],
        'result' => $result,
        'actual_result' => 'Observed outcome.',
        'evidence_summary' => 'Screenshot retained.',
        'deviation_id' => $deviation?->id,
        'executed_by' => $executor->id,
        'started_at' => now()->subHour(),
        'completed_at' => now()->subMinutes(10),
    ]);
}

it('approves a draft requirement with a verifiable signed decision', function (): void {
    $project = draftCsvProject();
    $requirement = draftRequirement($project);

    $approved = app(CsvRequirementApprovalService::class)->approve(
        $requirement,
        $this->approver,
        'Requirement acceptance criteria are complete and testable.',
        '203.0.113.10',
        'QualiGxP-CSV-Test/1.0',
    );
    $decision = $project->signedDecisions()->sole();

    expect($approved->status)->toBe(CsvRequirementStatus::Approved)
        ->and($approved->approved_by)->toBe($this->approver->id)
        ->and($approved->approved_at)->not->toBeNull()
        ->and($decision->decision_code)->toBe('approved')
        ->and($decision->subject_id)->toBe($requirement->id)
        ->and($decision->signature_hash)->not->toBeNull()
        ->and(app(ElectronicSignatureVerifier::class)->isValid($decision))->toBeTrue()
        ->and(fn () => $decision->update(['reason' => 'tampered']))
        ->toThrow(LogicException::class);
});

it('approves specifications and test cases through signed services', function (): void {
    $project = draftCsvProject();
    $specification = draftSpecification($project);
    $testCase = draftTestCase($project);

    $approvedSpec = app(CsvSpecificationApprovalService::class)->approve(
        $specification,
        $this->approver,
        'Specification matches approved intended use.',
        '203.0.113.11',
        'QualiGxP-CSV-Test/1.0',
    );
    $approvedTest = app(CsvTestCaseApprovalService::class)->approve(
        $testCase,
        $this->approver,
        'Protocol steps and expected results are complete.',
        '203.0.113.12',
        'QualiGxP-CSV-Test/1.0',
    );

    expect($approvedSpec->status)->toBe(CsvRequirementStatus::Approved)
        ->and($approvedTest->status)->toBe(CsvRequirementStatus::Approved)
        ->and($project->signedDecisions()->count())->toBe(2)
        ->and(app(ElectronicSignatureVerifier::class)->isValid($project->signedDecisions()->first()))->toBeTrue();
});

it('reviews a completed execution with evidence and an independent reviewer', function (): void {
    $project = draftCsvProject();
    $testCase = draftTestCase($project);
    $execution = completedExecution($project, $testCase, $this->executor);
    $execution->attachments()->create([
        'disk' => 'local',
        'path' => 'qms/quality-attachments/'.Str::uuid().'.pdf',
        'original_name' => 'execution-evidence.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1_024,
        'content_hash' => hash('sha256', 'evidence'),
        'description' => 'Execution screenshot package',
        'uploaded_by' => $this->executor->id,
        'uploaded_at' => now(),
    ]);

    $reviewed = app(CsvTestExecutionReviewService::class)->review(
        $execution,
        $this->reviewer,
        'Evidence confirms the observed result matches the protocol.',
        '203.0.113.13',
        'QualiGxP-CSV-Test/1.0',
    );
    $decision = $project->signedDecisions()->sole();

    expect($reviewed->reviewed_by)->toBe($this->reviewer->id)
        ->and($reviewed->reviewed_at)->not->toBeNull()
        ->and($decision->decision_code)->toBe('reviewed')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($decision))->toBeTrue();
});

it('enforces separation of duties between executor and reviewer', function (): void {
    $project = draftCsvProject();
    $testCase = draftTestCase($project);
    $execution = completedExecution($project, $testCase, $this->executor);
    $execution->attachments()->create([
        'disk' => 'local',
        'path' => 'qms/quality-attachments/'.Str::uuid().'.pdf',
        'original_name' => 'execution-evidence.pdf',
        'uploaded_by' => $this->executor->id,
        'uploaded_at' => now(),
    ]);
    $this->executor->givePermissionTo('Review:CsvValidationProject');

    expect(fn () => app(CsvTestExecutionReviewService::class)->review(
        $execution,
        $this->executor,
        'Self review attempt.',
    ))->toThrow(ValidationException::class);
});

it('blocks CSV signed decisions when QMS is disabled', function (): void {
    config()->set('modules.enabled', ['dms']);
    $project = draftCsvProject();
    $requirement = draftRequirement($project);

    expect(fn () => app(CsvRequirementApprovalService::class)->approve(
        $requirement,
        $this->approver,
        'Should fail closed.',
    ))->toThrow(ModuleNotEnabledException::class);
});

it('blocks review when evidence is missing', function (): void {
    $project = draftCsvProject();
    $testCase = draftTestCase($project);
    $execution = completedExecution($project, $testCase, $this->executor);

    expect(fn () => app(CsvTestExecutionReviewService::class)->review(
        $execution,
        $this->reviewer,
        'Missing evidence package.',
    ))->toThrow(ValidationException::class);
});

it('accepts residual risk with a signed decision', function (): void {
    $project = draftCsvProject();
    $risk = CsvRiskAssessment::query()->create([
        'csv_validation_project_id' => $project->id,
        'risk_identifier' => 'RA-001',
        'hazard' => 'Unauthorized change',
        'potential_impact' => 'Data integrity loss',
        'initial_severity' => 5,
        'initial_probability' => 3,
        'initial_detectability' => 3,
        'mitigation' => 'Access control and audit trail',
        'residual_severity' => 5,
        'residual_probability' => 1,
        'residual_detectability' => 1,
    ]);

    $accepted = app(CsvRiskAcceptanceService::class)->accept(
        $risk,
        $this->reviewer,
        'Residual RPN is controlled for intended use.',
        '203.0.113.14',
        'QualiGxP-CSV-Test/1.0',
    );

    expect($accepted->accepted_by)->toBe($this->reviewer->id)
        ->and($accepted->accepted_at)->not->toBeNull()
        ->and($project->signedDecisions()->sole()->decision_code)->toBe('accepted')
        ->and(app(ElectronicSignatureVerifier::class)->isValid($project->signedDecisions()->sole()))->toBeTrue();
});

it('blocks validation review while a failed execution has an open deviation', function (): void {
    Permission::findOrCreate('Review:CsvValidationProject', 'web');
    $this->reviewer->givePermissionTo('Review:CsvValidationProject');

    $project = CsvValidationProject::factory()->create([
        'status' => CsvValidationProjectStatus::Testing,
    ]);
    $testCase = draftTestCase($project);
    $openDeviation = Deviation::factory()->create([
        'status' => DeviationStatus::Open,
    ]);
    completedExecution(
        $project,
        $testCase,
        $this->executor,
        CsvExecutionResult::Failed,
        $openDeviation,
    );

    expect(fn () => app(CsvValidationProjectService::class)->transition(
        $project,
        CsvValidationProjectStatus::ValidationReview,
        $this->reviewer,
        'Attempt validation review with open deviation.',
    ))->toThrow(ValidationException::class);
});

it('allows validation review after failed-execution deviations are closed', function (): void {
    $project = CsvValidationProject::factory()->create([
        'status' => CsvValidationProjectStatus::Testing,
    ]);
    $testCase = draftTestCase($project);
    $closedDeviation = Deviation::factory()->create([
        'status' => DeviationStatus::Closed,
        'closed_at' => now(),
    ]);
    completedExecution(
        $project,
        $testCase,
        $this->executor,
        CsvExecutionResult::Failed,
        $closedDeviation,
    );

    $advanced = app(CsvValidationProjectService::class)->transition(
        $project,
        CsvValidationProjectStatus::ValidationReview,
        $this->reviewer,
        'All failed-execution deviations are closed.',
    );

    expect($advanced->status)->toBe(CsvValidationProjectStatus::ValidationReview);
});

it('rejects unauthorized CSV requirement approval', function (): void {
    $unauthorized = User::factory()->create();
    $project = draftCsvProject();
    $requirement = draftRequirement($project);

    expect(fn () => app(CsvRequirementApprovalService::class)->approve(
        $requirement,
        $unauthorized,
        'No permission.',
    ))->toThrow(AuthorizationException::class);
});
