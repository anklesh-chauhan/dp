<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\ChangeControlStatus;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Filament\Support\MyApprovalQueueService;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    config()->set('modules.enabled', ['dms', 'qms']);
    $this->seed(DatabaseSeeder::class);
});

it('lets the seeded SOP checker see pending change control quality approvals', function (): void {
    $checker = User::query()->where('email', 'Checker@example.com')->firstOrFail();
    $author = User::query()->where('email', 'Maker@example.com')->firstOrFail();
    $department = Department::query()->where('code', 'QA')->firstOrFail();
    $checkerRole = Role::findByName('sop checker', 'web');
    $workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $department,
        'subject_type' => ChangeControl::class,
        'is_active' => true,
    ]);
    $step = QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'role_id' => $checkerRole,
        'department_id' => $department,
    ]);
    $changeControl = ChangeControl::factory()->create([
        'department_id' => $department,
        'requested_by' => $author,
        'status' => ChangeControlStatus::Submitted,
        'submitted_at' => now(),
    ]);
    QualityApprovalInstance::factory()->create([
        'subject_type' => ChangeControl::class,
        'subject_id' => $changeControl,
        'workflow_id' => $workflow,
        'workflow_step_id' => $step,
    ]);

    $items = app(MyApprovalQueueService::class)->forUser($checker);
    $changeControlItem = $items->firstWhere('work_type', 'Change Control');

    expect($checker->can('Decide:QualityApproval'))->toBeTrue()
        ->and($checker->can('View:ChangeControl'))->toBeTrue()
        ->and($changeControlItem)->not->toBeNull()
        ->and($changeControlItem['reference'])->toBe($changeControl->change_number)
        ->and($changeControlItem['required_role'])->toBe('sop checker')
        ->and($changeControlItem['print_preview_url'])->toBeNull();
});

it('lets the seeded SOP checker see pending deviation quality approvals', function (): void {
    $checker = User::query()->where('email', 'Checker@example.com')->firstOrFail();
    $author = User::query()->where('email', 'Maker@example.com')->firstOrFail();
    $department = Department::query()->where('code', 'QA')->firstOrFail();
    $checkerRole = Role::findByName('sop checker', 'web');
    $workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $department,
        'subject_type' => Deviation::class,
        'is_active' => true,
    ]);
    $step = QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'role_id' => $checkerRole,
        'department_id' => $department,
    ]);
    $deviation = Deviation::factory()->create([
        'department_id' => $department,
        'reported_by' => $author,
        'status' => DeviationStatus::Open,
    ]);
    QualityApprovalInstance::factory()->create([
        'subject_type' => Deviation::class,
        'subject_id' => $deviation,
        'workflow_id' => $workflow,
        'workflow_step_id' => $step,
    ]);

    $items = app(MyApprovalQueueService::class)->forUser($checker);
    $deviationItem = $items->firstWhere('work_type', 'Deviation');

    expect($checker->can('Investigate:Deviation'))->toBeTrue()
        ->and($checker->can('View:Deviation'))->toBeTrue()
        ->and($deviationItem)->not->toBeNull()
        ->and($deviationItem['reference'])->toBe($deviation->deviation_number)
        ->and($deviationItem['required_role'])->toBe('sop checker');
});
