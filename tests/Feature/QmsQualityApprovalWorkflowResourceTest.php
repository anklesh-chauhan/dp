<?php

declare(strict_types=1);

use App\Domain\QMS\Models\ChangeControl;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\QualityApprovalInstance;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Filament\Resources\QualityApprovalWorkflows\Pages\CreateQualityApprovalWorkflow;
use App\Filament\Resources\QualityApprovalWorkflows\Pages\EditQualityApprovalWorkflow;
use App\Filament\Resources\QualityApprovalWorkflows\Pages\ListQualityApprovalWorkflows;
use App\Filament\Resources\QualityApprovalWorkflows\QualityApprovalWorkflowResource;
use App\Filament\Resources\QualityApprovalWorkflows\RelationManagers\WorkflowStepsRelationManager;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\QmsModuleSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);

    $this->permissions = [
        'ViewAny:QualityApprovalWorkflow',
        'View:QualityApprovalWorkflow',
        'Create:QualityApprovalWorkflow',
        'Update:QualityApprovalWorkflow',
        'Delete:QualityApprovalWorkflow',
        'DeleteAny:QualityApprovalWorkflow',
    ];

    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('owns quality workflow permissions and exposes the Filament resource', function (): void {
    expect(QmsModuleSeeder::PERMISSIONS)
        ->toContain(
            'ViewAny:QualityApprovalWorkflow',
            'View:QualityApprovalWorkflow',
            'Create:QualityApprovalWorkflow',
            'Update:QualityApprovalWorkflow',
            'Delete:QualityApprovalWorkflow',
            'DeleteAny:QualityApprovalWorkflow',
        )
        ->and(class_exists(QualityApprovalWorkflowResource::class))->toBeTrue();
});

it('registers the resource only when QMS is entitled', function (): void {
    expect(QualityApprovalWorkflowResource::canAccess())->toBeTrue()
        ->and(QualityApprovalWorkflowResource::shouldRegisterNavigation())->toBeTrue()
        ->and(QualityApprovalWorkflowResource::getNavigationGroup())->toBe('QMS')
        ->and(QualityApprovalWorkflowResource::getNavigationLabel())->toBe('Quality Workflows');

    config()->set('modules.enabled', ['dms']);

    expect(QualityApprovalWorkflowResource::canAccess())->toBeFalse()
        ->and(QualityApprovalWorkflowResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(QualityApprovalWorkflowResource::getUrl())->assertForbidden();
});

it('requires permissions for direct resource access', function (): void {
    $this->actingAs(User::factory()->create());

    Livewire::test(ListQualityApprovalWorkflows::class)
        ->assertForbidden();
});

it('creates a global quality workflow and redirects to edit so steps can be added', function (): void {
    Livewire::test(CreateQualityApprovalWorkflow::class)
        ->fillForm([
            'workflow_code' => 'qwf-dev-qa',
            'name' => 'Deviation QA Review',
            'subject_type' => Deviation::class,
            'department_id' => null,
            'is_active' => true,
            'description' => 'Global deviation review.',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $workflow = QualityApprovalWorkflow::query()->sole();

    expect($workflow->workflow_code)->toBe('QWF-DEV-QA')
        ->and($workflow->name)->toBe('Deviation QA Review')
        ->and($workflow->subject_type)->toBe(Deviation::class)
        ->and($workflow->department_id)->toBeNull()
        ->and($workflow->is_active)->toBeTrue();
});

it('creates a department quality workflow for change control', function (): void {
    $department = Department::factory()->create();

    Livewire::test(CreateQualityApprovalWorkflow::class)
        ->fillForm([
            'workflow_code' => 'QWF-CC-QA',
            'name' => 'QA Change Control Approval',
            'subject_type' => ChangeControl::class,
            'department_id' => $department->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $workflow = QualityApprovalWorkflow::query()->sole();

    expect($workflow->subject_type)->toBe(ChangeControl::class)
        ->and($workflow->department?->is($department))->toBeTrue();
});

it('lists quality workflows for authorized users', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create([
        'name' => 'Site Deviation Review',
    ]);

    Livewire::test(ListQualityApprovalWorkflows::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$workflow]);
});

it('adds ordered role and department steps from the edit screen', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create();
    $role = Role::findOrCreate('quality reviewer', 'web');
    $department = Department::factory()->create();

    Livewire::test(WorkflowStepsRelationManager::class, [
        'ownerRecord' => $workflow,
        'pageClass' => EditQualityApprovalWorkflow::class,
    ])
        ->callAction(TestAction::make('create')->table(), [
            'step_no' => 1,
            'role_id' => $role->id,
            'department_id' => $department->id,
            'is_mandatory' => true,
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $step = $workflow->steps()->sole();

    expect($step->step_no)->toBe(1)
        ->and($step->role_id)->toBe($role->id)
        ->and($step->department?->is($department))->toBeTrue()
        ->and($step->is_mandatory)->toBeTrue();
});

it('hides delete when the workflow already has approval instances', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create();
    QualityApprovalWorkflowStep::factory()->create(['workflow_id' => $workflow]);
    QualityApprovalInstance::factory()->create([
        'workflow_id' => $workflow,
        'workflow_step_id' => $workflow->steps()->sole()->id,
    ]);

    expect(QualityApprovalWorkflowResource::canDelete($workflow))->toBeFalse();

    Livewire::test(EditQualityApprovalWorkflow::class, ['record' => $workflow->getKey()])
        ->assertSuccessful()
        ->assertActionHidden(DeleteAction::class);
});

it('locks a workflow definition and its steps after approval history exists', function (): void {
    $workflow = QualityApprovalWorkflow::factory()->create();
    $step = QualityApprovalWorkflowStep::factory()->create(['workflow_id' => $workflow]);
    QualityApprovalInstance::factory()->create([
        'workflow_id' => $workflow,
        'workflow_step_id' => $step,
    ]);

    expect($workflow->fresh()?->isDefinitionMutable())->toBeFalse()
        ->and(fn () => $workflow->update(['name' => 'Changed historical workflow']))
        ->toThrow(LogicException::class)
        ->and(fn () => $step->update(['step_no' => 2]))
        ->toThrow(LogicException::class)
        ->and(fn () => QualityApprovalWorkflowStep::factory()->create(['workflow_id' => $workflow]))
        ->toThrow(LogicException::class);

    $workflow->refresh();

    expect(fn () => $workflow->update(['is_active' => false]))->not->toThrow(LogicException::class);

    Livewire::test(WorkflowStepsRelationManager::class, [
        'ownerRecord' => $workflow,
        'pageClass' => EditQualityApprovalWorkflow::class,
    ])
        ->assertActionHidden(TestAction::make('create')->table())
        ->assertActionHidden(TestAction::make('edit')->table($step))
        ->assertActionHidden(TestAction::make('delete')->table($step));
});
