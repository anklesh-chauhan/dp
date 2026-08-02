<?php

declare(strict_types=1);

use App\Domain\QMS\Enums\DeviationSeverity;
use App\Domain\QMS\Enums\DeviationStatus;
use App\Domain\QMS\Models\Deviation;
use App\Domain\QMS\Models\DeviationAuditEvent;
use App\Domain\QMS\Models\QualityApprovalWorkflow;
use App\Domain\QMS\Models\QualityApprovalWorkflowStep;
use App\Filament\Resources\Deviations\DeviationResource;
use App\Filament\Resources\Deviations\Pages\CreateDeviation;
use App\Filament\Resources\Deviations\Pages\EditDeviation;
use App\Filament\Resources\Deviations\Pages\ListDeviations;
use App\Filament\Resources\Deviations\Pages\ViewDeviation;
use App\Filament\Resources\Deviations\RelationManagers\ApprovalInstancesRelationManager;
use App\Filament\Resources\Deviations\RelationManagers\AuditEventsRelationManager;
use App\Models\Department;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('modules.enabled', ['dms', 'qms']);
    $this->permissions = [
        'ViewAny:Deviation',
        'View:Deviation',
        'Create:Deviation',
        'Update:Deviation',
        'Submit:Deviation',
    ];
    foreach ($this->permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $this->user = User::factory()->create();
    $this->user->givePermissionTo($this->permissions);
    $this->actingAs($this->user);
});

it('enforces QMS entitlement and permissions for resource access', function (): void {
    expect(DeviationResource::canAccess())->toBeTrue()
        ->and(DeviationResource::shouldRegisterNavigation())->toBeTrue()
        ->and(DeviationResource::getNavigationGroup())->toBe('QMS');

    config()->set('modules.enabled', ['dms']);

    expect(DeviationResource::canAccess())->toBeFalse()
        ->and(DeviationResource::shouldRegisterNavigation())->toBeFalse();

    $this->get(DeviationResource::getUrl())->assertForbidden();

    config()->set('modules.enabled', ['dms', 'qms']);
    $this->actingAs(User::factory()->create());

    Livewire::test(ListDeviations::class)->assertForbidden();
});

it('creates an attributed draft and only permits draft editing', function (): void {
    $department = Department::factory()->create();
    $owner = User::factory()->create();

    Livewire::test(CreateDeviation::class)
        ->fillForm([
            'title' => 'Temperature excursion in warehouse',
            'description' => 'The monitored zone exceeded the approved temperature range.',
            'immediate_actions' => 'Quarantined potentially affected materials.',
            'severity' => DeviationSeverity::Major->value,
            'occurred_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'discovered_at' => now()->format('Y-m-d H:i:s'),
            'department_id' => $department->id,
            'owner_id' => $owner->id,
            'investigation_due_at' => today()->addDays(14)->format('Y-m-d'),
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $deviation = Deviation::query()->sole();

    expect($deviation->deviation_number)->toStartWith('DEV-')
        ->and($deviation->reported_by)->toBe($this->user->id)
        ->and($deviation->status)->toBe(DeviationStatus::Draft)
        ->and(DeviationResource::canEdit($deviation))->toBeTrue();

    $deviation->update(['status' => DeviationStatus::Open]);

    expect(DeviationResource::canEdit($deviation->fresh()))->toBeFalse();
    Livewire::test(EditDeviation::class, ['record' => $deviation->id])->assertForbidden();
});

it('delegates lifecycle actions and exposes immutable audit history', function (): void {
    $deviation = Deviation::factory()->create(['reported_by' => $this->user]);

    Livewire::test(ViewDeviation::class, ['record' => $deviation->id])
        ->assertSuccessful()
        ->callAction('submit', ['reason' => 'Quality event is ready for triage.'])
        ->assertNotified();

    expect($deviation->fresh()?->status)->toBe(DeviationStatus::Open)
        ->and(DeviationAuditEvent::query()
            ->whereBelongsTo($deviation)
            ->where('to_status', DeviationStatus::Open->value)
            ->exists())->toBeTrue();

    Livewire::test(AuditEventsRelationManager::class, [
        'ownerRecord' => $deviation->fresh(),
        'pageClass' => ViewDeviation::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($deviation->auditEvents)
        ->assertActionDoesNotExist(TestAction::make('create')->table())
        ->assertActionDoesNotExist(TestAction::make('edit')->table())
        ->assertActionDoesNotExist(TestAction::make('delete')->table());
});

it('shows read-only pending steps when submission uses a quality workflow', function (): void {
    $deviation = Deviation::factory()->create(['reported_by' => $this->user]);
    $workflow = QualityApprovalWorkflow::factory()->create([
        'department_id' => $deviation->department_id,
    ]);
    $reviewerRole = Role::findOrCreate('quality reviewer', 'web');
    QualityApprovalWorkflowStep::factory()->create([
        'workflow_id' => $workflow,
        'role_id' => $reviewerRole->id,
    ]);

    Livewire::test(ViewDeviation::class, ['record' => $deviation->id])
        ->callAction('submit', ['reason' => 'Submit for configured quality review.'])
        ->assertNotified();

    $instances = $deviation->approvalInstances;

    expect($instances)->toHaveCount(1)
        ->and($instances->first()?->decision_code)->toBe('pending');

    Livewire::test(ApprovalInstancesRelationManager::class, [
        'ownerRecord' => $deviation->fresh(),
        'pageClass' => ViewDeviation::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($instances)
        ->assertActionDoesNotExist(TestAction::make('create')->table())
        ->assertActionDoesNotExist(TestAction::make('edit')->table())
        ->assertActionDoesNotExist(TestAction::make('delete')->table());

    foreach (['Decide:QualityApproval', 'Investigate:Deviation'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
    $reviewer = User::factory()->create(['department_id' => $deviation->department_id]);
    $reviewer->assignRole($reviewerRole);
    $reviewer->givePermissionTo([
        'View:Deviation',
        'Decide:QualityApproval',
        'Investigate:Deviation',
    ]);
    $this->actingAs($reviewer);

    Livewire::test(ApprovalInstancesRelationManager::class, [
        'ownerRecord' => $deviation->fresh(),
        'pageClass' => ViewDeviation::class,
    ])
        ->callAction(TestAction::make('approve')->table($instances->first()), [
            'comments' => 'Quality review completed in the workspace.',
        ])
        ->assertNotified();

    expect($deviation->fresh()?->status)->toBe(DeviationStatus::UnderInvestigation)
        ->and($instances->first()?->fresh()?->decision_code)->toBe('approved');
});
